<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * FI-M003: Add credit status fields for automated credit hold
     *
     * Enables automated credit hold when customers have severely overdue invoices.
     * Credit status workflow:
     * - active: Normal operations
     * - hold: Temporary hold due to overdue invoices (60+ days)
     * - blocked: Permanent block (manual intervention required)
     */
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->enum('credit_status', ['active', 'hold', 'blocked'])
                ->default('active')
                ->after('credit_limit');

            $table->timestamp('credit_hold_at')->nullable()->after('credit_status');
            $table->text('credit_hold_reason')->nullable()->after('credit_hold_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['credit_status', 'credit_hold_at', 'credit_hold_reason']);
        });
    }
};
