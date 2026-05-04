<?php

namespace App\Http\Controllers;

use App\Models\Depreciation;
use App\Models\Expense;
use App\Models\FiscalYear;
use App\Models\Revenue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BalanceSheetController extends Controller
{
    /**
     * 現在の事業体IDを取得
     */
    private function currentEntityId(): ?int
    {
        return session('current_entity_id');
    }

    public function index(Request $request)
    {
        $entityId = $this->currentEntityId();
        $currentYear = $request->input('year', date('Y'));
        
        $fiscalYear = FiscalYear::firstOrCreate(
            ['year' => $currentYear, 'entity_id' => $entityId],
            ['entity_id' => $entityId]
        );
        
        $years = FiscalYear::where('entity_id', $entityId)
            ->orderBy('year', 'desc')->pluck('year');

        // === 資産の部 ===
        // 現金・預金（売上 - 経費の残高として簡易計算）
        $revenueTotal = Revenue::where('fiscal_year_id', $fiscalYear->id)
            ->where('entity_id', $entityId)->sum('amount');
        $expenseTotal = Expense::where('fiscal_year_id', $fiscalYear->id)
            ->where('entity_id', $entityId)
            ->whereNotNull('account_category_id')->sum('amount');
        $unclassifiedExpense = Expense::where('fiscal_year_id', $fiscalYear->id)
            ->where('entity_id', $entityId)
            ->whereNull('account_category_id')->sum('amount');

        // 固定資産（減価償却後の帳簿価額）
        $depreciations = Depreciation::where('fiscal_year_id', $fiscalYear->id)
            ->where('entity_id', $entityId)->get();
        $fixedAssetTotal = $depreciations->sum('book_value');
        $acquisitionTotal = $depreciations->sum('acquisition_cost');
        $accumulatedDepTotal = $depreciations->sum('accumulated_depreciation');
        $depreciationAmount = $depreciations->sum('depreciation_amount');

        // === 資産項目 ===
        $assets = [
            'current' => [
                ['name' => '現金・預金', 'amount' => max(0, $revenueTotal - $expenseTotal - $unclassifiedExpense)],
            ],
            'fixed' => [],
        ];

        foreach ($depreciations as $dep) {
            $assets['fixed'][] = [
                'name' => $dep->asset_name,
                'acquisition_cost' => $dep->acquisition_cost,
                'accumulated_depreciation' => $dep->accumulated_depreciation,
                'book_value' => $dep->book_value,
            ];
        }

        $currentAssetTotal = collect($assets['current'])->sum('amount');
        $totalAssets = $currentAssetTotal + $fixedAssetTotal;

        // === 負債の部 ===
        // 簡易版: 未払金（未仕訳分を仮計上）
        $liabilities = [];
        if ($unclassifiedExpense > 0) {
            $liabilities[] = ['name' => '未払金（未仕訳分）', 'amount' => $unclassifiedExpense];
        }
        $totalLiabilities = collect($liabilities)->sum('amount');

        // === 純資産の部 ===
        $netIncome = $revenueTotal - $expenseTotal - $depreciationAmount;
        $totalEquity = $totalAssets - $totalLiabilities;

        return view('bs.index', compact(
            'currentYear', 'years', 'assets', 'liabilities',
            'currentAssetTotal', 'fixedAssetTotal', 'totalAssets',
            'totalLiabilities', 'totalEquity', 'netIncome',
            'acquisitionTotal', 'accumulatedDepTotal'
        ));
    }
}
