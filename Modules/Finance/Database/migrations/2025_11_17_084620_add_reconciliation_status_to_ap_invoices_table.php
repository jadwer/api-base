<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * PU-M002: Add reconciliation tracking fields to AP Invoices
     */
    public function up(): void
    {
        Schema::table('ap_invoices', function (Blueprint $table) {
            $table->enum('reconciliation_status', ['pending', 'matched', 'discrepancy', 'approved'])
                  ->default('pending')
                  ->after('status');

            $table->timestamp('reconciled_at')->nullable()->after('reconciliation_status');
            $table->unsignedBigInteger('reconciled_by')->nullable()->after('reconciled_at');
            $table->text('reconciliation_notes')->nullable()->after('reconciled_by');
            $table->json('discrepancies')->nullable()->after('reconciliation_notes');

            $table->foreign('reconciled_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ap_invoices', function (Blueprint $table) {
            $table->dropForeign(['reconciled_by']);
            $table->dropColumn([
                'reconciliation_status',
                'reconciled_at',
                'reconciled_by',
                'reconciliation_notes',
                'discrepancies',
            ]);
        });
    }
};
