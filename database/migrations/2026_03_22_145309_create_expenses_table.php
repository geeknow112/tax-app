<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('vendor_name');           // 利用場所
            $table->string('description')->nullable(); // 利用内容
            $table->integer('amount');                // 金額（円）
            $table->string('payment_method')->default('credit_card'); // credit_card, cash, paypay
            $table->foreignId('account_category_id')->nullable()->constrained()->nullOnDelete(); // 仕訳結果
            $table->text('memo')->nullable();
            $table->timestamps();

            $table->index(['fiscal_year_id', 'account_category_id']);
            $table->index('vendor_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
