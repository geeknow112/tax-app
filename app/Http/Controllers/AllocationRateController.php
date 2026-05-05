<?php

namespace App\Http\Controllers;

use App\Models\AccountCategory;
use App\Models\AllocationRate;
use App\Models\Entity;
use Illuminate\Http\Request;

class AllocationRateController extends Controller
{
    public function index()
    {
        $entities = Entity::all();
        $categories = AccountCategory::orderBy('sort_order')->get();

        // 按分率を取得（科目×事業体のマトリクス）
        $rates = AllocationRate::all()
            ->groupBy('account_category_id')
            ->map(fn($items) => $items->keyBy('entity_id'));

        return view('allocation-rates.index', compact('entities', 'categories', 'rates'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'account_category_id' => 'required|exists:account_categories,id',
            'entity_id' => 'required|exists:entities,id',
            'rate' => 'required|numeric|min:0|max:100',
        ]);

        $categoryId = $request->account_category_id;
        $entityId = $request->entity_id;
        $newRate = (float) $request->rate;

        // 更新対象の按分率を保存
        AllocationRate::updateOrCreate(
            ['account_category_id' => $categoryId, 'entity_id' => $entityId],
            ['rate' => $newRate]
        );

        // 他の事業体の按分率を自動調整（合計100%に）
        $entities = Entity::all();
        $otherEntities = $entities->where('id', '!=', $entityId);
        
        if ($otherEntities->count() === 1) {
            // 2事業体の場合：残りを自動計算
            $otherEntity = $otherEntities->first();
            $otherRate = 100 - $newRate;
            
            AllocationRate::updateOrCreate(
                ['account_category_id' => $categoryId, 'entity_id' => $otherEntity->id],
                ['rate' => max(0, $otherRate)]
            );
        }

        // 更新後の全按分率を返す
        $updatedRates = AllocationRate::where('account_category_id', $categoryId)
            ->get()
            ->keyBy('entity_id')
            ->map(fn($r) => $r->rate);

        return response()->json([
            'success' => true,
            'rates' => $updatedRates,
        ]);
    }
}
