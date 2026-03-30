<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['estimate', 'order', 'invoice', 'delivery']); // 見積書/発注書/請求書/納品書
            $table->string('document_number')->unique(); // 書類番号
            $table->date('issue_date'); // 発行日
            $table->date('due_date')->nullable(); // 支払期限/納期
            $table->string('client_name'); // 取引先名
            $table->string('client_address')->nullable();
            $table->string('subject')->nullable(); // 件名
            $table->integer('subtotal')->default(0); // 小計
            $table->integer('tax')->default(0); // 消費税
            $table->integer('total')->default(0); // 合計
            $table->enum('status', ['draft', 'sent', 'paid', 'cancelled'])->default('draft');
            $table->text('notes')->nullable(); // 備考
            $table->timestamps();
        });

        Schema::create('document_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('description'); // 品目
            $table->integer('quantity')->default(1);
            $table->string('unit')->default('式'); // 単位
            $table->integer('unit_price')->default(0); // 単価
            $table->integer('amount')->default(0); // 金額
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_items');
        Schema::dropIfExists('documents');
    }
};
