<?php

namespace App\Http\Controllers;

use App\Models\AccountCategory;
use App\Models\Expense;
use App\Models\FiscalYear;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    /**
     * 仕訳画面
     */
    public function index(Request $request)
    {
        $currentYear = $request->input('year', date('Y'));
        $filter = $request->input('filter', 'all'); // all, unclassified, classified
        $search = $request->input('search', '');
        $paymentMethod = $request->input('payment_method', 'all'); // all, credit_card, cash, paypay

        $fiscalYear = FiscalYear::firstOrCreate(['year' => $currentYear]);
        $prevYear = FiscalYear::where('year', $currentYear - 1)->first();

        // 今年の経費
        $query = Expense::where('fiscal_year_id', $fiscalYear->id)
            ->with('accountCategory');

        if ($filter === 'unclassified') {
            $query->whereNull('account_category_id');
        } elseif ($filter === 'classified') {
            $query->whereNotNull('account_category_id');
        }

        if ($paymentMethod !== 'all') {
            $query->where('payment_method', $paymentMethod);
        }

        if ($search) {
            $query->where('vendor_name', 'like', "%{$search}%");
        }

        $expenses = $query->orderBy('date')->paginate(50);

        // 去年の経費（参照用）
        $prevExpenses = collect();
        if ($prevYear) {
            $prevQuery = Expense::where('fiscal_year_id', $prevYear->id)
                ->whereNotNull('account_category_id')
                ->with('accountCategory');
            if ($search) {
                $prevQuery->where('vendor_name', 'like', "%{$search}%");
            }
            $prevExpenses = $prevQuery->orderBy('date')->get();
        }

        $categories = AccountCategory::orderBy('sort_order')->get();
        $years = FiscalYear::orderBy('year', 'desc')->pluck('year');

        // 集計
        $totalCount = Expense::where('fiscal_year_id', $fiscalYear->id)->count();
        $classifiedCount = Expense::where('fiscal_year_id', $fiscalYear->id)
            ->whereNotNull('account_category_id')->count();

        return view('expenses.index', compact(
            'expenses', 'prevExpenses', 'categories', 'years',
            'currentYear', 'filter', 'search', 'paymentMethod',
            'totalCount', 'classifiedCount'
        ));
    }

    /**
     * 経費を新規追加（AJAX）
     */
    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'vendor_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:credit_card,cash,paypay',
            'account_category_id' => 'nullable|exists:account_categories,id',
            'year' => 'required|integer',
        ]);

        $fiscalYear = FiscalYear::firstOrCreate(['year' => $request->year]);

        $expense = Expense::create([
            'fiscal_year_id' => $fiscalYear->id,
            'date' => $request->date,
            'vendor_name' => $request->vendor_name,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'account_category_id' => $request->account_category_id ?: null,
            'memo' => '',
        ]);

        return response()->json([
            'success' => true,
            'expense' => $expense->load('accountCategory'),
        ]);
    }

    /**
     * 仕訳を更新（AJAX）
     */
    public function classify(Request $request, Expense $expense)
    {
        $request->validate([
            'account_category_id' => 'nullable|exists:account_categories,id',
        ]);

        $expense->update([
            'account_category_id' => $request->account_category_id,
        ]);

        return response()->json([
            'success' => true,
            'category_name' => $expense->fresh()->accountCategory?->name,
        ]);
    }

    /**
     * 去年の類似明細を検索（AJAX）
     */
    public function searchPrevYear(Request $request)
    {
        $vendorName = $request->input('vendor_name', '');
        $currentYear = $request->input('year', date('Y'));
        $prevYear = FiscalYear::where('year', $currentYear - 1)->first();

        if (!$prevYear || !$vendorName) {
            return response()->json([]);
        }

        $results = Expense::where('fiscal_year_id', $prevYear->id)
            ->where('vendor_name', 'like', "%{$vendorName}%")
            ->whereNotNull('account_category_id')
            ->with('accountCategory')
            ->orderBy('date')
            ->limit(20)
            ->get();

        return response()->json($results);
    }

    /**
     * チェックした明細に一括で科目を適用（AJAX）
     */
    public function bulkClassify(Request $request)
    {
        $request->validate([
            'expense_ids' => 'required|array|min:1',
            'expense_ids.*' => 'exists:expenses,id',
            'account_category_id' => 'required|exists:account_categories,id',
        ]);

        $updated = Expense::whereIn('id', $request->expense_ids)
            ->update(['account_category_id' => $request->account_category_id]);

        $categoryName = AccountCategory::find($request->account_category_id)?->name;

        return response()->json([
            'success' => true,
            'updated_count' => $updated,
            'category_name' => $categoryName,
        ]);
    }
}
