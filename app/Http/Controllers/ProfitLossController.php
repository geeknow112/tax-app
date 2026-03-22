<?php

namespace App\Http\Controllers;

use App\Models\AccountCategory;
use App\Models\Expense;
use App\Models\FiscalYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfitLossController extends Controller
{
    public function index(Request $request)
    {
        $currentYear = $request->input('year', date('Y'));
        $fiscalYear = FiscalYear::firstOrCreate(['year' => $currentYear]);
        $years = FiscalYear::orderBy('year', 'desc')->pluck('year');

        // 科目別集計
        $expensesByCategory = Expense::where('fiscal_year_id', $fiscalYear->id)
            ->whereNotNull('account_category_id')
            ->select('account_category_id', DB::raw('SUM(amount) as total'))
            ->groupBy('account_category_id')
            ->pluck('total', 'account_category_id');

        // 未仕訳の合計
        $unclassifiedTotal = Expense::where('fiscal_year_id', $fiscalYear->id)
            ->whereNull('account_category_id')
            ->sum('amount');

        // 全科目を取得（0円の科目も表示するため）
        $categories = AccountCategory::orderBy('sort_order')->get();

        // P/Lデータ構築
        $plItems = [];
        $expenseTotal = 0;

        foreach ($categories as $cat) {
            $amount = $expensesByCategory[$cat->id] ?? 0;
            $plItems[] = [
                'name' => $cat->name,
                'amount' => $amount,
            ];
            $expenseTotal += $amount;
        }

        // 月別集計
        $monthlyTotals = Expense::where('fiscal_year_id', $fiscalYear->id)
            ->whereNotNull('account_category_id')
            ->select(
                DB::raw('MONTH(date) as month'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy(DB::raw('MONTH(date)'))
            ->pluck('total', 'month')
            ->toArray();

        // 12ヶ月分を埋める
        $monthly = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthly[$m] = $monthlyTotals[$m] ?? 0;
        }

        // 仕訳状況
        $totalCount = Expense::where('fiscal_year_id', $fiscalYear->id)->count();
        $classifiedCount = Expense::where('fiscal_year_id', $fiscalYear->id)
            ->whereNotNull('account_category_id')->count();

        return view('pl.index', compact(
            'currentYear', 'years', 'plItems', 'expenseTotal',
            'unclassifiedTotal', 'monthly', 'totalCount', 'classifiedCount'
        ));
    }
}
