<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 既存データを事業体に紐付ける
     */
    public function up(): void
    {
        // 個人事業のID
        $individualId = DB::table('entities')->where('type', 'individual')->value('id');
        // 法人のID
        $corporationId = DB::table('entities')->where('type', 'corporation')->value('id');

        if (!$individualId || !$corporationId) {
            throw new \Exception('Entities not found. Run EntitySeeder first.');
        }

        // 経費: すべて個人事業に紐付け（後で手動振り分け）
        DB::table('expenses')->whereNull('entity_id')->update(['entity_id' => $individualId]);

        // 売上: すべて法人に紐付け（TCSのデータのため）
        DB::table('revenues')->whereNull('entity_id')->update(['entity_id' => $corporationId]);

        // 減価償却: すべて個人事業に紐付け（後で確認・振り分け）
        DB::table('depreciations')->whereNull('entity_id')->update(['entity_id' => $individualId]);

        // 勘定科目: すべて個人事業に紐付け（共通で使う場合はNULLのままでも可）
        DB::table('account_categories')->whereNull('entity_id')->update(['entity_id' => $individualId]);

        // 年度: すべて個人事業に紐付け
        DB::table('fiscal_years')->whereNull('entity_id')->update(['entity_id' => $individualId]);

        // 書類: すべて個人事業に紐付け
        DB::table('documents')->whereNull('entity_id')->update(['entity_id' => $individualId]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // entity_id を NULL に戻す
        DB::table('expenses')->update(['entity_id' => null]);
        DB::table('revenues')->update(['entity_id' => null]);
        DB::table('depreciations')->update(['entity_id' => null]);
        DB::table('account_categories')->update(['entity_id' => null]);
        DB::table('fiscal_years')->update(['entity_id' => null]);
        DB::table('documents')->update(['entity_id' => null]);
    }
};
