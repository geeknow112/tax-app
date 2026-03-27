<?php

namespace App\Console\Commands;

use App\Models\FiscalYear;
use App\Models\Revenue;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ImportRevenuesFromExcel extends Command
{
    protected $signature = 'import:revenues {file} {--year=2025}';
    protected $description = 'TCS_売上シートから売上データをインポート';

    public function handle(): int
    {
        $filePath = $this->argument('file');
        $year = $this->option('year');

        if (!file_exists($filePath)) {
            $this->error("ファイルが見つかりません: {$filePath}");
            return 1;
        }

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getSheetByName('TCS_売上');

        if (!$sheet) {
            $this->error("TCS_売上シートが見つかりません");
            return 1;
        }

        $fiscalYear = FiscalYear::firstOrCreate(['year' => $year]);

        // 既存データ削除
        $deleted = Revenue::where('fiscal_year_id', $fiscalYear->id)->delete();
        if ($deleted > 0) {
            $this->info("既存の売上データ {$deleted}件を削除しました");
        }

        $rows = $sheet->toArray(null, true, true);
        $imported = 0;

        // TCS_売上: D列=年月, E列=金額 (行4から)
        foreach ($rows as $i => $row) {
            if ($i < 3) continue; // ヘッダースキップ

            $dateStr = trim($row[3] ?? ''); // D列
            $amountStr = trim($row[4] ?? ''); // E列

            if (empty($dateStr) || empty($amountStr)) continue;

            // 年月パース (2025/04 形式)
            $date = $this->parseYearMonth($dateStr, $sheet->getCell('D' . ($i + 1))->getValue());
            if (!$date) continue;

            $amount = abs(intval(str_replace(',', '', $amountStr)));
            if ($amount === 0) continue;

            // 年度フィルタ
            $dateYear = (int) date('Y', strtotime($date));
            if ($dateYear != $year && $dateYear != $year + 1) continue;

            Revenue::create([
                'fiscal_year_id' => $fiscalYear->id,
                'date' => $date,
                'client_name' => 'TCS',
                'description' => '業務委託報酬',
                'amount' => $amount,
                'revenue_type' => 'sales',
            ]);
            $imported++;
        }

        $this->info("完了: 売上 {$imported}件インポート");
        return 0;
    }

    private function parseYearMonth($displayValue, $rawValue): ?string
    {
        // Excelシリアル値
        if (is_numeric($rawValue) && $rawValue > 40000) {
            try {
                $dt = ExcelDate::excelToDateTimeObject($rawValue);
                return $dt->format('Y-m-d');
            } catch (\Exception $e) {}
        }

        // 文字列 "2025/04" 形式
        if (is_string($displayValue) && preg_match('/(\d{4})\/(\d{1,2})/', $displayValue, $m)) {
            return "{$m[1]}-{$m[2]}-01";
        }

        // DateTimeオブジェクト
        if ($displayValue instanceof \DateTimeInterface) {
            return $displayValue->format('Y-m-d');
        }

        return null;
    }
}
