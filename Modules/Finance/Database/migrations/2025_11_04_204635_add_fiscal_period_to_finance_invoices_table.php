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
        // Add fiscal_period_id to AR invoices
        if (Schema::hasTable('ar_invoices')) {
            Schema::table('ar_invoices', function (Blueprint $table) {
                $table->foreignId('fiscal_period_id')
                    ->nullable()
                    ->after('journal_entry_id')
                    ->constrained('fiscal_periods')
                    ->onDelete('restrict');
            });
        }

        // Add fiscal_period_id to AP invoices
        if (Schema::hasTable('ap_invoices')) {
            Schema::table('ap_invoices', function (Blueprint $table) {
                $table->foreignId('fiscal_period_id')
                    ->nullable()
                    ->after('journal_entry_id')
                    ->constrained('fiscal_periods')
                    ->onDelete('restrict');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('ar_invoices')) {
            Schema::table('ar_invoices', function (Blueprint $table) {
                $table->dropForeign(['fiscal_period_id']);
                $table->dropColumn('fiscal_period_id');
            });
        }

        if (Schema::hasTable('ap_invoices')) {
            Schema::table('ap_invoices', function (Blueprint $table) {
                $table->dropForeign(['fiscal_period_id']);
                $table->dropColumn('fiscal_period_id');
            });
        }
    }
};
