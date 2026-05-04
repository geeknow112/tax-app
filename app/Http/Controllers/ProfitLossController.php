<?php

namespace App\Http\Controllers;

use App\Models\AccountCategory;
use App\Models\Depreciation;
use App\Models\Expense;
use App\Models\FiscalYear;
use App\Models\Revenue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfitLossController extends Controller
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

        // === 売上集計 ===
        $revenueTotal = Revenue::where('fiscal_year_id', $fiscalYear->id)
            ->where('entity_id', $entityId)
            ->where('revenue_type', 'sales')->sum('amount');
        $otherIncomeTotal = Revenue::where('fiscal_year_id', $fiscalYear->id)
            ->where('entity_id', $entityId)
            ->where('revenue_type', 'other')->sum('amount');

        // 売上月別
        $monthlyRevenue = [];
        $revenueByMonth = Revenue::where('fiscal_year_id', $fiscalYear->id)
            ->where('entity_id', $entityId)
            ->select(DB::raw('MONTH(date) as month'), DB::raw('SUM(amount) as total'))
            ->groupBy(DB::raw('MONTH(date)'))
            ->pluck('total', 'month')->toArray();
        for ($m = 1; $m <= 12; $m++) {
            $monthlyRevenue[$m] = $revenueByMonth[$m] ?? 0;
        }

        // === 経費 科目×支払方法別集計 ===
        $expensesByCategoryAndMethod = Expense::where('fiscal_year_id', $fiscalYear->id)
            ->where('entity_id', $entityId)
            ->whereNotNull('account_category_id')
            ->select('account_category_id', 'payment_method', DB::raw('SUM(amount) as total'))
            ->groupBy('account_category_id', 'payment_method')
            ->get();

        $categoryMethodMap = [];
        foreach ($expensesByCategoryAndMethod as $row) {
            $categoryMethodMap[$row->account_category_id][$row->payment_method] = $row->total;
        }

        $unclassifiedTotal = Expense::where('fiscal_year_id', $fiscalYear->id)
            ->where('entity_id', $entityId)
            ->whereNull('account_category_id')->sum('amount');

        $categories = AccountCategory::where(function($q) use ($entityId) {
            $q->where('entity_id', $entityId)->orWhereNull('entity_id');
        })->orderBy('sort_order')->get();

        $plItems = [];
        $expenseTotal = 0;
        $totalByMethod = ['credit_card' => 0, 'cash' => 0, 'paypay' => 0];

        foreach ($categories as $cat) {
            $methods = $categoryMethodMap[$cat->id] ?? [];
            $creditCard = $methods['credit_card'] ?? 0;
            $cash = $methods['cash'] ?? 0;
            $paypay = $methods['paypay'] ?? 0;
            $amount = $creditCard + $cash + $paypay;

            $plItems[] = [
                'name' => $cat->name,
                'amount' => $amount,
                'credit_card' => $creditCard,
                'cash' => $cash,
                'paypay' => $paypay,
            ];
            $expenseTotal += $amount;
            $totalByMethod['credit_card'] += $creditCard;
            $totalByMethod['cash'] += $cash;
            $totalByMethod['paypay'] += $paypay;
        }

        // === 減価償却費 ===
        $depreciationTotal = Depreciation::where('fiscal_year_id', $fiscalYear->id)
            ->where('entity_id', $entityId)
            ->sum('depreciation_amount');

        // === 月別経費集計 ===
        $monthlyTotals = Expense::where('fiscal_year_id', $fiscalYear->id)
            ->where('entity_id', $entityId)
            ->whereNotNull('account_category_id')
            ->select(DB::raw('MONTH(date) as month'), DB::raw('SUM(amount) as total'))
            ->groupBy(DB::raw('MONTH(date)'))
            ->pluck('total', 'month')->toArray();

        $monthly = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthly[$m] = $monthlyTotals[$m] ?? 0;
        }

        // === P/L サマリー ===
        $grossProfit = $revenueTotal - $expenseTotal - $depreciationTotal;
        $netIncome = $grossProfit + $otherIncomeTotal;

        // 仕訳状況
        $totalCount = Expense::where('fiscal_year_id', $fiscalYear->id)
            ->where('entity_id', $entityId)->count();
        $classifiedCount = Expense::where('fiscal_year_id', $fiscalYear->id)
            ->where('entity_id', $entityId)
            ->whereNotNull('account_category_id')->count();

        return view('pl.index', compact(
            'currentYear', 'years', 'plItems', 'expenseTotal',
            'unclassifiedTotal', 'monthly', 'totalCount', 'classifiedCount',
            'totalByMethod', 'revenueTotal', 'otherIncomeTotal',
            'depreciationTotal', 'grossProfit', 'netIncome',
            'monthlyRevenue'
        ));
    }
}
