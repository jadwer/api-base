<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FI-M002: Early Payment Discounts (Pronto Pago)
 *
 * Adds fields to support early payment discount terms like:
 * - 2/10 Net 30: 2% discount if paid within 10 days, otherwise due in 30 days
 * - 1/15 Net 45: 1% discount if paid within 15 days, otherwise due in 45 days
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ar_invoices', function (Blueprint $table) {
            // Early payment discount terms
            $table->decimal('discount_percent', 5, 2)->nullable()->after('total_amount')
                  ->comment('Early payment discount percentage (e.g., 2.00 for 2%)');
            $table->integer('discount_days')->nullable()->after('discount_percent')
                  ->comment('Days within which payment qualifies for discount');
            $table->date('discount_date')->nullable()->after('discount_days')
                  ->comment('Calculated deadline for discount eligibility');
            $table->decimal('discount_amount', 15, 2)->nullable()->after('discount_date')
                  ->comment('Calculated discount amount');

            // Tracking if discount was applied
            $table->boolean('discount_applied')->default(false)->after('discount_amount');
            $table->decimal('discount_applied_amount', 15, 2)->nullable()->after('discount_applied')
                  ->comment('Actual discount amount applied on payment');
            $table->date('discount_applied_date')->nullable()->after('discount_applied_amount');

            // Indexes
            $table->index(['discount_date', 'discount_applied']);
        });
    }

    public function down(): void
    {
        Schema::table('ar_invoices', function (Blueprint $table) {
            $table->dropIndex(['discount_date', 'discount_applied']);

            $table->dropColumn([
                'discount_percent',
                'discount_days',
                'discount_date',
                'discount_amount',
                'discount_applied',
                'discount_applied_amount',
                'discount_applied_date',
            ]);
        });
    }
};
