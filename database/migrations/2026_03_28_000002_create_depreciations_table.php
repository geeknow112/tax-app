<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depreciations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained()->cascadeOnDelete();
            $table->string('asset_name');              // 資産名
            $table->date('acquisition_date');           // 取得日
            $table->integer('acquisition_cost');        // 取得価額
            $table->integer('useful_life');             // 耐用年数
            $table->string('method')->default('straight_line'); // straight_line(定額法), declining_balance(定率法)
            $table->integer('depreciation_amount')->default(0); // 当年償却額
            $table->integer('accumulated_depreciation')->default(0); // 累計償却額
            $table->integer('book_value')->default(0);  // 期末帳簿価額
            $table->text('memo')->nullable();
            $table->timestamps();

            $table->index('fiscal_year_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depreciations');
    }
};
