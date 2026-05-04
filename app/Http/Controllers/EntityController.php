<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use Illuminate\Http\Request;

class EntityController extends Controller
{
    /**
     * 事業体を切り替える
     */
    public function switch(Request $request)
    {
        $request->validate([
            'entity_id' => 'required|exists:entities,id',
        ]);

        session(['current_entity_id' => $request->entity_id]);

        return redirect()->back()->with('success', '事業体を切り替えました');
    }

    /**
     * 事業体一覧
     */
    public function index()
    {
        $entities = Entity::all();
        return view('entities.index', compact('entities'));
    }

    /**
     * 事業体作成フォーム
     */
    public function create()
    {
        return view('entities.create');
    }

    /**
     * 事業体を保存
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:individual,corporation',
            'fiscal_year_start' => 'required|integer|min:1|max:12',
        ]);

        Entity::create($request->only(['name', 'type', 'fiscal_year_start']));

        return redirect()->route('entities.index')->with('success', '事業体を作成しました');
    }

    /**
     * 事業体編集フォーム
     */
    public function edit(Entity $entity)
    {
        return view('entities.edit', compact('entity'));
    }

    /**
     * 事業体を更新
     */
    public function update(Request $request, Entity $entity)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:individual,corporation',
            'fiscal_year_start' => 'required|integer|min:1|max:12',
        ]);

        $entity->update($request->only(['name', 'type', 'fiscal_year_start']));

        return redirect()->route('entities.index')->with('success', '事業体を更新しました');
    }

    /**
     * 事業体を削除
     */
    public function destroy(Entity $entity)
    {
        // 関連データがある場合は削除不可
        if ($entity->expenses()->exists() || $entity->revenues()->exists()) {
            return redirect()->back()->with('error', '関連データがあるため削除できません');
        }

        $entity->delete();

        return redirect()->route('entities.index')->with('success', '事業体を削除しました');
    }
}
