<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * yearのユニーク制約を削除し、entity_id + year の複合ユニークに変更
     */
    public function up(): void
    {
        Schema::table('fiscal_years', function (Blueprint $table) {
            // 既存のyearユニーク制約を削除
            $table->dropUnique(['year']);
            
            // entity_id + year の複合ユニーク制約を追加
            $table->unique(['entity_id', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fiscal_years', function (Blueprint $table) {
            $table->dropUnique(['entity_id', 'year']);
            $table->unique('year');
        });
    }
};
