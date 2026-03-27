<?php

namespace App\Http\Controllers;

use App\Models\AccountCategory;
use Illuminate\Http\Request;

class AccountCategoryController extends Controller
{
    public function index()
    {
        $categories = AccountCategory::orderBy('sort_order')->get();
        return view('categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:account_categories,name',
        ]);

        $maxSort = AccountCategory::max('sort_order') ?? 0;

        AccountCategory::create([
            'name' => $request->name,
            'sort_order' => $maxSort + 1,
        ]);

        return redirect()->route('categories.index')->with('success', '勘定科目を追加しました');
    }

    public function update(Request $request, AccountCategory $category)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:account_categories,name,' . $category->id,
        ]);

        $category->update(['name' => $request->name]);

        return response()->json(['success' => true, 'name' => $category->name]);
    }

    public function destroy(AccountCategory $category)
    {
        $usageCount = $category->expenses()->count();
        if ($usageCount > 0) {
            return redirect()->route('categories.index')
                ->with('error', "「{$category->name}」は{$usageCount}件の経費で使用中のため削除できません");
        }

        $category->delete();
        return redirect()->route('categories.index')->with('success', '勘定科目を削除しました');
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:account_categories,id',
        ]);

        foreach ($request->ids as $index => $id) {
            AccountCategory::where('id', $id)->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
    }
}
