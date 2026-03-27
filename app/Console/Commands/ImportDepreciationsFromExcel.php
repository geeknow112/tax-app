<?php

namespace App\Console\Commands;

use App\Models\Depreciation;
use App\Models\FiscalYear;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ImportDepreciationsFromExcel extends Command
{
    protected $signature = 'import:depreciations {file} {--year=2025}';
    protected $description = '減価償却費明細書シートから固定資産データをインポート';

    public function handle(): int
    {
        $filePath = $this->argument('file');
        $year = $this->option('year');

        if (!file_exists($filePath)) {
            $this->error("ファイルが見つかりません: {$filePath}");
            return 1;
        }

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getSheetByName('減価償却費明細書');

        if (!$sheet) {
            $this->error("減価償却費明細書シートが見つかりません");
            return 1;
        }

        $fiscalYear = FiscalYear::firstOrCreate(['year' => $year]);

        // 既存データ削除
        $deleted = Depreciation::where('fiscal_year_id', $fiscalYear->id)->delete();
        if ($deleted > 0) {
            $this->info("既存の減価償却データ {$deleted}件を削除しました");
        }

        $rows = $sheet->toArray(null, true, true);
        $imported = 0;

        // 減価償却費明細書: A=No, B=科目, C=資産名, D=取得年月, E=取得価額, F=未償却残高, G=耐用年数, H=償却率, I=月数, J=当期償却費, K=期末簿価, L=備考
        // 行4からデータ開始
        foreach ($rows as $i => $row) {
            if ($i < 3) continue; // ヘッダースキップ

            $no = trim($row[0] ?? '');
            if (!is_numeric($no)) continue; // No列が数値でなければスキップ

            $assetName = trim($row[2] ?? ''); // C列
            if (empty($assetName)) continue;

            $dateStr = trim($row[3] ?? ''); // D列: 取得年月
            $acquisitionCost = abs(intval(str_replace(',', '', $row[4] ?? '0'))); // E列
            $usefulLife = intval($row[6] ?? '0'); // G列
            $depreciationAmount = abs(intval(str_replace(',', '', $row[9] ?? '0'))); // J列
            $memo = trim($row[11] ?? ''); // L列

            if ($acquisitionCost === 0 || $usefulLife === 0) continue;

            // 取得日パース
            $acquisitionDate = $this->parseDate($dateStr, $sheet->getCell('D' . ($i + 1))->getValue());
            if (!$acquisitionDate) continue;

            // 累計償却額 = 取得価額 - 未償却残高
            $remainingValue = abs(intval(str_replace(',', '', $row[5] ?? '0'))); // F列
            $accumulatedPrev = $acquisitionCost - $remainingValue - $depreciationAmount;
            if ($accumulatedPrev < 0) $accumulatedPrev = 0;
            $accumulated = $accumulatedPrev + $depreciationAmount;
            $bookValue = $acquisitionCost - $accumulated;

            Depreciation::create([
                'fiscal_year_id' => $fiscalYear->id,
                'asset_name' => $assetName,
                'acquisition_date' => $acquisitionDate,
                'acquisition_cost' => $acquisitionCost,
                'useful_life' => $usefulLife,
                'method' => 'straight_line',
                'depreciation_amount' => $depreciationAmount,
                'accumulated_depreciation' => $accumulated,
                'book_value' => max($bookValue, 0),
                'memo' => $memo,
            ]);
            $imported++;
        }

        $this->info("完了: 減価償却 {$imported}件インポート");
        return 0;
    }

    private function parseDate($displayValue, $rawValue): ?string
    {
        // Excelシリアル値
        if (is_numeric($rawValue) && $rawValue > 40000) {
            try {
                $dt = ExcelDate::excelToDateTimeObject($rawValue);
                return $dt->format('Y-m-d');
            } catch (\Exception $e) {}
        }

        // "2024/11/23" 形式
        if (is_string($displayValue)) {
            $ts = strtotime($displayValue);
            if ($ts !== false) {
                return date('Y-m-d', $ts);
            }
        }

        // DateTimeオブジェクト
        if ($displayValue instanceof \DateTimeInterface) {
            return $displayValue->format('Y-m-d');
        }

        return null;
    }
}
