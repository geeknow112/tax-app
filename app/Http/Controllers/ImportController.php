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

        $fiscalYear = FiscalYear::firstOrCreate(['year' => $request->year]);
        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getPathname());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $imported = 0;
        $categoryMap = AccountCategory::pluck('id', 'name')->toArray();

        if ($request->type === 'credit_card') {
            $imported = $this->importCreditCard($rows, $fiscalYear, $categoryMap);
        } else {
            $imported = $this->importCash($rows, $fiscalYear, $categoryMap);
        }

        return redirect()->route('import.show')
            ->with('success', "{$imported}件インポートしました（{$request->year}年 / {$request->type}）");
    }

    private function importCreditCard(array $rows, FiscalYear $fiscalYear, array $categoryMap): int
    {
        $imported = 0;
        foreach ($rows as $i => $row) {
            if ($i < 2) continue; // ヘッダー2行スキップ
            if (empty($row[1])) continue; // 日付なし→スキップ

            $date = $this->parseDate($row[1]);
            if (!$date) continue;

            $vendorName = trim($row[2] ?? '');
            $amount = abs(intval($row[4] ?? 0));
            if (!$vendorName || $amount === 0) continue;

            // 科目列（I列=index 8）
            $categoryName = trim($row[8] ?? '');
            $categoryId = $categoryMap[$categoryName] ?? null;

            Expense::create([
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
        return $imported;
    }

    private function importCash(array $rows, FiscalYear $fiscalYear, array $categoryMap): int
    {
        $imported = 0;
        foreach ($rows as $i => $row) {
            if ($i < 2) continue;
            if (empty($row[1])) continue;

            $date = $this->parseDate($row[1]);
            if (!$date) continue;

            $vendorName = trim($row[2] ?? '') ?: trim($row[3] ?? '');
            $amount = abs(intval($row[4] ?? 0));
            if (!$vendorName || $amount === 0) continue;

            $categoryName = trim($row[8] ?? '');
            $categoryId = $categoryMap[$categoryName] ?? null;

            $paymentMethod = str_contains(strtolower($row[2] ?? ''), 'paypay') ? 'paypay' : 'cash';

            Expense::create([
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
        return $imported;
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
        try {
            return date('Y-m-d', strtotime($value));
        } catch (\Exception $e) {
            return null;
        }
    }
}
