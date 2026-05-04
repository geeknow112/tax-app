<?php

namespace App\Http\Controllers;

use App\Models\Depreciation;
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

        $depreciations = Depreciation::where('fiscal_year_id', $fiscalYear->id)
            ->where('entity_id', $entityId)
            ->orderBy('acquisition_date')
            ->get();

        $totalDepreciation = $depreciations->sum('depreciation_amount');
        $totalBookValue = $depreciations->sum('book_value');

        return view('depreciations.index', compact(
            'depreciations', 'years', 'currentYear',
            'totalDepreciation', 'totalBookValue'
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
