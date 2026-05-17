<?php

namespace App\Http\Controllers;

use App\Models\AccountCategory;
use App\Models\Expense;
use App\Models\FiscalYear;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportController extends Controller
{
    public function show()
    {
        $years = FiscalYear::orderBy('year', 'desc')->pluck('year');
        return view('import.show', compact('years'));
    }

    /**
     * Excelインポート処理
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'year' => 'required|integer|min:2020|max:2099',
            'type' => 'required|in:credit_card,cash',
        ]);

        $entityId = session('current_entity_id');
        $fiscalYear = FiscalYear::firstOrCreate(
            ['year' => $request->year, 'entity_id' => $entityId],
            ['entity_id' => $entityId]
        );
        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getPathname());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $categoryMap = AccountCategory::pluck('id', 'name')->toArray();

        if ($request->type === 'credit_card') {
            $result = $this->importCreditCard($rows, $fiscalYear, $categoryMap, $entityId);
        } else {
            $result = $this->importCash($rows, $fiscalYear, $categoryMap, $entityId);
        }

        $message = "{$result['imported']}件インポートしました（{$request->year}年 / {$request->type}）";
        if ($result['skipped'] > 0) {
            $message .= " ※{$result['skipped']}件は重複のためスキップしました";
        }

        return redirect()->route('import.show')
            ->with('success', $message);
    }

    /**
     * 重複チェック
     */
    private function isDuplicate(int $entityId, string $date, string $vendorName, int $amount): bool
    {
        return Expense::where('entity_id', $entityId)
            ->where('date', $date)
            ->where('vendor_name', $vendorName)
            ->where('amount', $amount)
            ->exists();
    }

    private function importCreditCard(array $rows, FiscalYear $fiscalYear, array $categoryMap, int $entityId): array
    {
        $imported = 0;
        $skipped = 0;
        foreach ($rows as $i => $row) {
            if ($i < 2) continue; // ヘッダー2行スキップ
            if (empty($row[1])) continue; // 日付なし→スキップ

            $date = $this->parseDate($row[1]);
            if (!$date) continue;

            $vendorName = trim($row[2] ?? '');
            $amount = intval(str_replace(',', '', $row[4] ?? 0));
            if (!$vendorName || $amount === 0) continue;

            // 重複チェック
            if ($this->isDuplicate($entityId, $date, $vendorName, $amount)) {
                $skipped++;
                continue;
            }

            // 科目列（I列=index 8）
            $categoryName = trim($row[8] ?? '');
            $categoryId = $categoryMap[$categoryName] ?? null;

            Expense::create([
                'entity_id' => $entityId,
                'fiscal_year_id' => $fiscalYear->id,
                'date' => $date,
                'vendor_name' => $vendorName,
                'description' => $row[3] ?? null,
                'amount' => $amount,
                'payment_method' => 'credit_card',
                'account_category_id' => $categoryId,
                'memo' => $row[7] ?? null,
            ]);
            $imported++;
        }
        return ['imported' => $imported, 'skipped' => $skipped];
    }

    private function importCash(array $rows, FiscalYear $fiscalYear, array $categoryMap, int $entityId): array
    {
        $imported = 0;
        $skipped = 0;
        foreach ($rows as $i => $row) {
            if ($i < 2) continue;
            if (empty($row[1])) continue;

            $date = $this->parseDate($row[1]);
            if (!$date) continue;

            $vendorName = trim($row[2] ?? '') ?: trim($row[3] ?? '');
            $amount = intval(str_replace(',', '', $row[4] ?? 0));
            if (!$vendorName || $amount === 0) continue;

            $paymentMethod = str_contains(strtolower($row[2] ?? ''), 'paypay') ? 'paypay' : 'cash';

            // 重複チェック
            if ($this->isDuplicate($entityId, $date, $vendorName, $amount)) {
                $skipped++;
                continue;
            }

            $categoryName = trim($row[8] ?? '');
            $categoryId = $categoryMap[$categoryName] ?? null;

            Expense::create([
                'entity_id' => $entityId,
                'fiscal_year_id' => $fiscalYear->id,
                'date' => $date,
                'vendor_name' => $vendorName,
                'description' => $row[3] ?? null,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'account_category_id' => $categoryId,
                'memo' => $row[7] ?? null,
            ]);
            $imported++;
        }
        return ['imported' => $imported, 'skipped' => $skipped];
    }

    private function parseDate($value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        if (is_numeric($value)) {
            // Excelシリアル値
            $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
            return $date->format('Y-m-d');
        }
        // 日本語形式: 2026年1月10日
        if (preg_match('/(\d{4})年(\d{1,2})月(\d{1,2})日/', $value, $m)) {
            return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
        }
        try {
            $ts = strtotime($value);
            if ($ts === false) {
                return null;
            }
            return date('Y-m-d', $ts);
        } catch (\Exception $e) {
            return null;
        }
    }
}
