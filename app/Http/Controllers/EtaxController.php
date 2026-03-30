<?php

namespace App\Http\Controllers;

use App\Models\AccountCategory;
use App\Models\Depreciation;
use App\Models\Expense;
use App\Models\FiscalYear;
use App\Models\Revenue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EtaxController extends Controller
{
    public function index(Request $request)
    {
        $currentYear = $request->input('year', date('Y'));
        $fiscalYear = FiscalYear::firstOrCreate(['year' => $currentYear]);
        $years = FiscalYear::orderBy('year', 'desc')->pluck('year');

        // 売上
        $salesTotal = Revenue::where('fiscal_year_id', $fiscalYear->id)
            ->where('revenue_type', 'sales')->sum('amount');
        $otherIncomeTotal = Revenue::where('fiscal_year_id', $fiscalYear->id)
            ->where('revenue_type', 'other')->sum('amount');

        // 経費 科目別集計
        $expenseByCategory = Expense::where('fiscal_year_id', $fiscalYear->id)
            ->whereNotNull('account_category_id')
            ->select('account_category_id', DB::raw('SUM(amount) as total'))
            ->groupBy('account_category_id')
            ->pluck('total', 'account_category_id')->toArray();

        $categories = AccountCategory::orderBy('sort_order')->get();
        $expenseItems = [];
        $expenseTotal = 0;
        foreach ($categories as $cat) {
            $amount = $expenseByCategory[$cat->id] ?? 0;
            if ($amount > 0) {
                $expenseItems[] = ['name' => $cat->name, 'amount' => $amount];
                $expenseTotal += $amount;
            }
        }

        // 減価償却
        $depreciations = Depreciation::where('fiscal_year_id', $fiscalYear->id)->get();
        $depreciationTotal = $depreciations->sum('depreciation_amount');

        // 所得計算
        $totalExpense = $expenseTotal + $depreciationTotal;
        $income = $salesTotal + $otherIncomeTotal - $totalExpense;

        return view('etax.index', compact(
            'currentYear', 'years', 'salesTotal', 'otherIncomeTotal',
            'expenseItems', 'expenseTotal', 'depreciations', 'depreciationTotal',
            'totalExpense', 'income'
        ));
    }
}
