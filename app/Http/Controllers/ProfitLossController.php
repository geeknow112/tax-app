<?php

namespace App\Http\Controllers;

use App\Models\AccountCategory;
use App\Models\AllocationRate;
use App\Models\Depreciation;
use App\Models\Entity;
use App\Models\Expense;
use App\Models\FiscalYear;
use App\Models\Revenue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfitLossController extends Controller
{
    /**
     * 按分率を取得（科目ID => 按分率%）
     */
    private function getAllocationRates(int $entityId): array
    {
        return AllocationRate::where('entity_id', $entityId)
            ->pluck('rate', 'account_category_id')
            ->toArray();
    }

    /**
     * 現在の事業体IDを取得
     */
    private function currentEntityId(): ?int
    {
        return session('current_entity_id');
    }

    /**
     * 事業年度の月順序を取得（4月決算なら [4,5,6,7,8,9,10,11,12,1,2,3]）
     */
    private function getFiscalMonthOrder(Entity $entity): array
    {
        $start = $entity->fiscal_year_start;
        $months = [];
        for ($i = 0; $i < 12; $i++) {
            $m = (($start - 1 + $i) % 12) + 1;
            $months[] = $m;
        }
        return $months;
    }

    public function index(Request $request)
    {
        $entityId = $this->currentEntityId();
        $entity = Entity::find($entityId);
        $currentYear = $request->input('year', date('Y'));
        
        $fiscalYear = FiscalYear::firstOrCreate(
            ['year' => $currentYear, 'entity_id' => $entityId],
            ['entity_id' => $entityId]
        );
        
        $years = FiscalYear::where('entity_id', $entityId)
            ->orderBy('year', 'desc')->pluck('year');

        // 事業年度の期間を取得
        $fiscalPeriod = $entity->getFiscalPeriod($currentYear);
        $fiscalMonthOrder = $this->getFiscalMonthOrder($entity);

        // === 売上集計 ===
        $revenueTotal = Revenue::where('fiscal_year_id', $fiscalYear->id)
            ->where('entity_id', $entityId)
            ->where('revenue_type', 'sales')->sum('amount');
        $otherIncomeTotal = Revenue::where('fiscal_year_id', $fiscalYear->id)
            ->where('entity_id', $entityId)
            ->where('revenue_type', 'other')->sum('amount');

        // 売上月別（月でグループ化 - fiscal_year_idで既に絞られている）
        $revenueByMonth = Revenue::where('fiscal_year_id', $fiscalYear->id)
            ->where('entity_id', $entityId)
            ->select(DB::raw('MONTH(date) as month'), DB::raw('SUM(amount) as total'))
            ->groupBy(DB::raw('MONTH(date)'))
            ->pluck('total', 'month')
            ->toArray();
        
        $monthlyRevenue = [];
        foreach ($fiscalMonthOrder as $m) {
            $monthlyRevenue[$m] = $revenueByMonth[$m] ?? 0;
        }

        // === 按分率を取得 ===
        $allocationRates = $this->getAllocationRates($entityId);

        // === 経費 科目×支払方法別集計（按分率適用） ===
        $expensesByCategoryAndMethod = Expense::where('fiscal_year_id', $fiscalYear->id)
            ->where('entity_id', $entityId)
            ->whereNotNull('account_category_id')
            ->select('account_category_id', 'payment_method', DB::raw('SUM(amount) as total'))
            ->groupBy('account_category_id', 'payment_method')
            ->get();

        $categoryMethodMap = [];
        foreach ($expensesByCategoryAndMethod as $row) {
            $rate = ($allocationRates[$row->account_category_id] ?? 100) / 100;
            $categoryMethodMap[$row->account_category_id][$row->payment_method] = (int) round($row->total * $rate);
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
                'rate' => $allocationRates[$cat->id] ?? 100,
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

        // === 月別経費集計（按分率適用） ===
        $expenseByMonthAndCategory = Expense::where('fiscal_year_id', $fiscalYear->id)
            ->where('entity_id', $entityId)
            ->whereNotNull('account_category_id')
            ->select('account_category_id', DB::raw('MONTH(date) as month'), DB::raw('SUM(amount) as total'))
            ->groupBy('account_category_id', DB::raw('MONTH(date)'))
            ->get();

        $monthlyRaw = [];
        foreach ($expenseByMonthAndCategory as $row) {
            $rate = ($allocationRates[$row->account_category_id] ?? 100) / 100;
            $allocated = (int) round($row->total * $rate);
            $monthlyRaw[$row->month] = ($monthlyRaw[$row->month] ?? 0) + $allocated;
        }

        $monthly = [];
        foreach ($fiscalMonthOrder as $m) {
            $monthly[$m] = $monthlyRaw[$m] ?? 0;
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
            'monthlyRevenue', 'fiscalMonthOrder', 'entity'
        ));
    }
}
