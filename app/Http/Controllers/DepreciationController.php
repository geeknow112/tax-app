<?php

namespace App\Http\Controllers;

use App\Models\AccountCategory;
use App\Models\AllocationRate;
use App\Models\Depreciation;
use App\Models\Entity;
use App\Models\FiscalYear;
use Illuminate\Http\Request;

class DepreciationController extends Controller
{
    /**
     * 現在の事業体IDを取得
     */
    private function currentEntityId(): ?int
    {
        return session('current_entity_id');
    }

    /**
     * 減価償却費の按分率を取得
     */
    private function getDepreciationRate(int $entityId): float
    {
        $category = AccountCategory::where('name', '減価償却費')->first();
        if (!$category) {
            return 100;
        }
        
        $rate = AllocationRate::where('account_category_id', $category->id)
            ->where('entity_id', $entityId)
            ->value('rate');
        
        return $rate ?? 100;
    }

    public function index(Request $request)
    {
        $entityId = $this->currentEntityId();
        $currentYear = $request->input('year', date('Y'));
        
        $fiscalYear = FiscalYear::firstOrCreate(
            ['year' => $currentYear, 'entity_id' => $entityId],
            ['entity_id' => $entityId]
        );
        
        // 全事業体の年度を取得
        $allYears = FiscalYear::orderBy('year', 'desc')->pluck('year')->unique();
        $years = $allYears->isNotEmpty() ? $allYears : collect([$currentYear]);

        // 按分率を取得
        $depreciationRate = $this->getDepreciationRate($entityId);

        // 全事業体の減価償却データを取得（同じ年度）
        $allFiscalYearIds = FiscalYear::where('year', $currentYear)->pluck('id');
        
        $allDepreciations = Depreciation::whereIn('fiscal_year_id', $allFiscalYearIds)
            ->with('entity')
            ->orderBy('acquisition_date')
            ->get();

        // 按分情報を追加
        $depreciations = $allDepreciations->map(function ($dep) use ($entityId, $depreciationRate) {
            $dep->is_allocated = ($dep->entity_id !== $entityId);
            $dep->allocation_rate = $depreciationRate;
            $dep->allocated_amount = (int) round($dep->depreciation_amount * $depreciationRate / 100);
            $dep->original_entity_name = $dep->entity->name ?? '';
            return $dep;
        });

        $totalDepreciation = $depreciations->sum('depreciation_amount');
        $totalBookValue = $depreciations->sum('book_value');
        $totalDepreciationAllocated = $depreciations->sum('allocated_amount');

        return view('depreciations.index', compact(
            'depreciations', 'years', 'currentYear',
            'totalDepreciation', 'totalBookValue',
            'depreciationRate', 'totalDepreciationAllocated'
        ));
    }

    public function store(Request $request)
    {
        $entityId = $this->currentEntityId();
        
        $request->validate([
            'asset_name' => 'required|string|max:255',
            'acquisition_date' => 'required|date',
            'acquisition_cost' => 'required|integer|min:1',
            'useful_life' => 'required|integer|min:1|max:50',
            'method' => 'required|in:straight_line,declining_balance',
        ]);

        $fiscalYear = FiscalYear::firstOrCreate(
            ['year' => $request->input('year', date('Y')), 'entity_id' => $entityId],
            ['entity_id' => $entityId]
        );

        $depreciation = new Depreciation([
            'entity_id' => $entityId,
            'fiscal_year_id' => $fiscalYear->id,
            'asset_name' => $request->asset_name,
            'acquisition_date' => $request->acquisition_date,
            'acquisition_cost' => $request->acquisition_cost,
            'useful_life' => $request->useful_life,
            'method' => $request->method,
            'memo' => $request->memo,
        ]);
        $depreciation->recalculate();
        $depreciation->save();

        return redirect()->route('depreciations.index', ['year' => $fiscalYear->year])
            ->with('success', '固定資産を登録しました');
    }

    public function destroy(Depreciation $depreciation)
    {
        $year = $depreciation->fiscalYear->year;
        $depreciation->delete();
        return redirect()->route('depreciations.index', ['year' => $year])
            ->with('success', '固定資産を削除しました');
    }
}
