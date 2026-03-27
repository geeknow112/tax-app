<?php

namespace App\Http\Controllers;

use App\Models\FiscalYear;
use App\Models\Revenue;
use Illuminate\Http\Request;

class RevenueController extends Controller
{
    public function index(Request $request)
    {
        $currentYear = $request->input('year', date('Y'));
        $fiscalYear = FiscalYear::firstOrCreate(['year' => $currentYear]);
        $years = FiscalYear::orderBy('year', 'desc')->pluck('year');

        $revenues = Revenue::where('fiscal_year_id', $fiscalYear->id)
            ->orderBy('date')
            ->paginate(50);

        $totalSales = Revenue::where('fiscal_year_id', $fiscalYear->id)
            ->where('revenue_type', 'sales')->sum('amount');
        $totalOther = Revenue::where('fiscal_year_id', $fiscalYear->id)
            ->where('revenue_type', 'other')->sum('amount');

        return view('revenues.index', compact(
            'revenues', 'years', 'currentYear', 'totalSales', 'totalOther'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'client_name' => 'required|string|max:255',
            'amount' => 'required|integer|min:1',
            'revenue_type' => 'required|in:sales,other',
        ]);

        $fiscalYear = FiscalYear::firstOrCreate(['year' => $request->input('year', date('Y'))]);

        Revenue::create([
            'fiscal_year_id' => $fiscalYear->id,
            'date' => $request->date,
            'client_name' => $request->client_name,
            'description' => $request->description,
            'amount' => $request->amount,
            'revenue_type' => $request->revenue_type,
            'memo' => $request->memo,
        ]);

        return redirect()->route('revenues.index', ['year' => $fiscalYear->year])
            ->with('success', '売上を登録しました');
    }

    public function destroy(Revenue $revenue)
    {
        $year = $revenue->fiscalYear->year;
        $revenue->delete();
        return redirect()->route('revenues.index', ['year' => $year])
            ->with('success', '売上を削除しました');
    }
}
