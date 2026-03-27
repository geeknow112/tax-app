<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revenues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('client_name');            // 取引先
            $table->string('description')->nullable(); // 内容
            $table->integer('amount');                 // 金額（円）
            $table->string('revenue_type')->default('sales'); // sales, other
            $table->text('memo')->nullable();
            $table->timestamps();

            $table->index(['fiscal_year_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revenues');
    }
};
