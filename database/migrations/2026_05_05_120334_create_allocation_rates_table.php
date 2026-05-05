<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('allocation_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('entity_id')->constrained()->cascadeOnDelete();
            $table->decimal('rate', 5, 2)->default(0); // 0.00 ~ 100.00
            $table->timestamps();

            $table->unique(['account_category_id', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('allocation_rates');
    }
};
