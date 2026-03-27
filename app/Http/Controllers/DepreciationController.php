<?php

namespace App\Http\Controllers;

use App\Models\Depreciation;
use App\Models\FiscalYear;
use Illuminate\Http\Request;

class DepreciationController extends Controller
{
    public function index(Request $request)
    {
        $currentYear = $request->input('year', date('Y'));
        $fiscalYear = FiscalYear::firstOrCreate(['year' => $currentYear]);
        $years = FiscalYear::orderBy('year', 'desc')->pluck('year');

        $depreciations = Depreciation::where('fiscal_year_id', $fiscalYear->id)
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
        $request->validate([
            'asset_name' => 'required|string|max:255',
            'acquisition_date' => 'required|date',
            'acquisition_cost' => 'required|integer|min:1',
            'useful_life' => 'required|integer|min:1|max:50',
            'method' => 'required|in:straight_line,declining_balance',
        ]);

        $fiscalYear = FiscalYear::firstOrCreate(['year' => $request->input('year', date('Y'))]);

        $depreciation = new Depreciation([
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
