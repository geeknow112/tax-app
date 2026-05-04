<?php

namespace App\Http\Controllers;

use App\Models\AccountCategory;
use App\Models\Expense;
use App\Models\FiscalYear;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    /**
     * 現在の事業体IDを取得
     */
    private function currentEntityId(): ?int
    {
        return session('current_entity_id');
    }

    /**
     * 仕訳画面
     */
    public function index(Request $request)
    {
        $entityId = $this->currentEntityId();
        $currentYear = $request->input('year', date('Y'));
        $filter = $request->input('filter', 'all'); // all, unclassified, classified
        $search = $request->input('search', '');
        $paymentMethod = $request->input('payment_method', 'all'); // all, credit_card, cash, paypay

        $fiscalYear = FiscalYear::firstOrCreate(
            ['year' => $currentYear, 'entity_id' => $entityId],
            ['entity_id' => $entityId]
        );
        $prevYear = FiscalYear::where('year', $currentYear - 1)
            ->where('entity_id', $entityId)
            ->first();

        // 今年の経費（事業体でフィルタ）
        $query = Expense::where('fiscal_year_id', $fiscalYear->id)
            ->where('entity_id', $entityId)
            ->with(['accountCategory', 'entity']);

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
                ->where('entity_id', $entityId)
                ->whereNotNull('account_category_id')
                ->with('accountCategory');
            if ($search) {
                $prevQuery->where('vendor_name', 'like', "%{$search}%");
            }
            $prevExpenses = $prevQuery->orderBy('date')->get();
        }

        $categories = AccountCategory::where(function($q) use ($entityId) {
            $q->where('entity_id', $entityId)->orWhereNull('entity_id');
        })->orderBy('sort_order')->get();
        
        $years = FiscalYear::where('entity_id', $entityId)
            ->orderBy('year', 'desc')->pluck('year');

        // 全事業体（事業体変更用）
        $allEntities = \App\Models\Entity::all();

        // 集計
        $totalCount = Expense::where('fiscal_year_id', $fiscalYear->id)
            ->where('entity_id', $entityId)->count();
        $classifiedCount = Expense::where('fiscal_year_id', $fiscalYear->id)
            ->where('entity_id', $entityId)
            ->whereNotNull('account_category_id')->count();

        return view('expenses.index', compact(
            'expenses', 'prevExpenses', 'categories', 'years',
            'currentYear', 'filter', 'search', 'paymentMethod',
            'totalCount', 'classifiedCount', 'allEntities'
        ));
    }

    /**
     * 経費を新規追加（AJAX）
     */
    public function store(Request $request)
    {
        $entityId = $this->currentEntityId();
        
        $request->validate([
            'date' => 'required|date',
            'vendor_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:credit_card,cash,paypay',
            'account_category_id' => 'nullable|exists:account_categories,id',
            'year' => 'required|integer',
        ]);

        $fiscalYear = FiscalYear::firstOrCreate(
            ['year' => $request->year, 'entity_id' => $entityId],
            ['entity_id' => $entityId]
        );

        $expense = Expense::create([
            'entity_id' => $entityId,
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
        $entityId = $this->currentEntityId();
        $vendorName = $request->input('vendor_name', '');
        $currentYear = $request->input('year', date('Y'));
        $prevYear = FiscalYear::where('year', $currentYear - 1)
            ->where('entity_id', $entityId)
            ->first();

        if (!$prevYear || !$vendorName) {
            return response()->json([]);
        }

        $results = Expense::where('fiscal_year_id', $prevYear->id)
            ->where('entity_id', $entityId)
            ->where('vendor_name', 'like', "%{$vendorName}%")
            ->whereNotNull('account_category_id')
            ->with('accountCategory')
            ->orderBy('date')
            ->limit(20)
            ->get();

        return response()->json($results);
    }

    /**
     * 経費を削除（AJAX）
     */
    public function destroy(Expense $expense)
    {
        $expense->delete();

        return response()->json([
            'success' => true,
        ]);
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

    /**
     * 経費の事業体を変更（AJAX）
     */
    public function changeEntity(Request $request, Expense $expense)
    {
        $request->validate([
            'entity_id' => 'required|exists:entities,id',
        ]);

        $newEntityId = $request->entity_id;
        $expense->entity_id = $newEntityId;

        // 新しい事業体の年度を取得または作成
        $year = $expense->fiscalYear?->year ?? date('Y');
        $newFiscalYear = FiscalYear::firstOrCreate(
            ['year' => $year, 'entity_id' => $newEntityId],
            ['entity_id' => $newEntityId]
        );
        $expense->fiscal_year_id = $newFiscalYear->id;
        $expense->save();

        return response()->json([
            'success' => true,
            'entity_name' => $expense->entity->name,
        ]);
    }

    /**
     * チェックした明細の事業体を一括変更（AJAX）
     */
    public function bulkChangeEntity(Request $request)
    {
        $request->validate([
            'expense_ids' => 'required|array|min:1',
            'expense_ids.*' => 'exists:expenses,id',
            'entity_id' => 'required|exists:entities,id',
        ]);

        $newEntityId = $request->entity_id;
        $entity = \App\Models\Entity::find($newEntityId);
        $updated = 0;

        foreach ($request->expense_ids as $expenseId) {
            $expense = Expense::find($expenseId);
            if ($expense) {
                $year = $expense->fiscalYear?->year ?? date('Y');
                $newFiscalYear = FiscalYear::firstOrCreate(
                    ['year' => $year, 'entity_id' => $newEntityId],
                    ['entity_id' => $newEntityId]
                );
                $expense->entity_id = $newEntityId;
                $expense->fiscal_year_id = $newFiscalYear->id;
                $expense->save();
                $updated++;
            }
        }

        return response()->json([
            'success' => true,
            'updated_count' => $updated,
            'entity_name' => $entity->name,
        ]);
    }
}
