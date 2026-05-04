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
        // fiscal_years に entity_id 追加
        Schema::table('fiscal_years', function (Blueprint $table) {
            $table->foreignId('entity_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        // expenses に entity_id 追加
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('entity_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        // revenues に entity_id 追加
        Schema::table('revenues', function (Blueprint $table) {
            $table->foreignId('entity_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        // depreciations に entity_id 追加
        Schema::table('depreciations', function (Blueprint $table) {
            $table->foreignId('entity_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        // account_categories に entity_id 追加
        Schema::table('account_categories', function (Blueprint $table) {
            $table->foreignId('entity_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        // documents に entity_id 追加
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('entity_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fiscal_years', function (Blueprint $table) {
            $table->dropForeign(['entity_id']);
            $table->dropColumn('entity_id');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['entity_id']);
            $table->dropColumn('entity_id');
        });

        Schema::table('revenues', function (Blueprint $table) {
            $table->dropForeign(['entity_id']);
            $table->dropColumn('entity_id');
        });

        Schema::table('depreciations', function (Blueprint $table) {
            $table->dropForeign(['entity_id']);
            $table->dropColumn('entity_id');
        });

        Schema::table('account_categories', function (Blueprint $table) {
            $table->dropForeign(['entity_id']);
            $table->dropColumn('entity_id');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['entity_id']);
            $table->dropColumn('entity_id');
        });
    }
};
