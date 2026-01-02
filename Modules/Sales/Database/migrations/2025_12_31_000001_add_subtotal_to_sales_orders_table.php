<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * F.2: Add subtotal column to sales_orders for consistency with other financial entities.
     *
     * Formula: subtotal = total_amount + discount_total (before discounts applied)
     * This allows clear separation: subtotal - discount_total = total_amount
     */
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->decimal('subtotal', 15, 2)->default(0)->after('discount_total');
            $table->decimal('tax_amount', 15, 2)->default(0)->after('subtotal');
        });

        // Backfill existing data: subtotal = total_amount + discount_total
        \DB::statement('UPDATE sales_orders SET subtotal = total_amount + discount_total');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'tax_amount']);
        });
    }
};
