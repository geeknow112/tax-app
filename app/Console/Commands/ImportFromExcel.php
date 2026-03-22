<?php

namespace App\Console\Commands;

use App\Models\AccountCategory;
use App\Models\Expense;
use App\Models\FiscalYear;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ImportFromExcel extends Command
{
    protected $signature = 'import:excel {file} {--sheet=} {--year=} {--type=credit_card}';
    protected $description = 'Excelファイルから経費データをインポート';

    public function handle(): int
    {
        $filePath = $this->argument('file');
        $sheetName = $this->option('sheet');
        $year = $this->option('year');
        $type = $this->option('type');

        if (!file_exists($filePath)) {
            $this->error("ファイルが見つかりません: {$filePath}");
            return 1;
        }

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $sheetName
            ? $spreadsheet->getSheetByName($sheetName)
            : $spreadsheet->getActiveSheet();

        if (!$sheet) {
            $this->error("シートが見つかりません: {$sheetName}");
            $this->info("利用可能なシート: " . implode(', ', $spreadsheet->getSheetNames()));
            return 1;
        }

        $rows = $sheet->toArray(null, true, true);
        $fiscalYear = FiscalYear::firstOrCreate(['year' => $year]);
        $categoryMap = AccountCategory::pluck('id', 'name')->toArray();

        $imported = 0;
        $skipped = 0;

        foreach ($rows as $i => $row) {
            if ($i < 2) continue; // ヘッダースキップ

            // 日付の取得
            $dateRaw = $row[1] ?? null; // B列
            if (empty($dateRaw)) continue;

            $date = $this->parseDate($dateRaw, $sheet->getCell('B' . ($i + 1))->getValue());
            if (!$date) { $skipped++; continue; }

            if ($type === 'credit_card') {
                $vendorName = trim($row[2] ?? '');
                $amount = abs(intval($row[4] ?? 0));
                $categoryName = trim($row[8] ?? ''); // I列
                $memo = trim($row[7] ?? '');          // H列
                $description = $row[3] ?? null;
                $paymentMethod = 'credit_card';
            } else {
                // 現金シート: B=日付, C=場所, D=内容, E=金額, I=科目
                $vendorName = trim($row[2] ?? '');
                if (empty($vendorName)) {
                    $vendorName = trim($row[3] ?? '');
                }
                $amount = abs(intval($row[4] ?? 0));
                $categoryName = trim($row[8] ?? ''); // I列
                $memo = trim($row[7] ?? '');
                $description = $row[3] ?? null;
                $paymentMethod = stripos($row[2] ?? '', 'paypay') !== false ? 'paypay' : 'cash';
            }

            if (empty($vendorName) || $amount === 0) { $skipped++; continue; }

            $categoryId = $categoryMap[$categoryName] ?? null;

            Expense::create([
                'fiscal_year_id' => $fiscalYear->id,
                'date' => $date,
                'vendor_name' => $vendorName,
                'description' => $description,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'account_category_id' => $categoryId,
                'memo' => $memo,
            ]);
            $imported++;
        }

        $this->info("完了: {$imported}件インポート, {$skipped}件スキップ");
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

        // DateTimeオブジェクト
        if ($displayValue instanceof \DateTimeInterface) {
            return $displayValue->format('Y-m-d');
        }

        // 文字列パース
        if (is_string($displayValue)) {
            $ts = strtotime($displayValue);
            if ($ts !== false) {
                return date('Y-m-d', $ts);
            }
        }

        return null;
    }
}
