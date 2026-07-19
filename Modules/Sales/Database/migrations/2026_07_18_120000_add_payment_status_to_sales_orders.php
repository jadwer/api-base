<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DESIGN_ECOMMERCE_PAGO_STOCK (H-C): estado de pago como dimension PROPIA de la
 * orden, separada de invoicing_status/financial_status (que significan
 * facturacion). Lo escriben SOLO los listeners de pago (readOnly por API).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->string('payment_status', 20)->default('unpaid')->after('financial_status')->index();
            $table->timestamp('paid_at')->nullable()->after('payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropIndex(['payment_status']);
            $table->dropColumn(['payment_status', 'paid_at']);
        });
    }
};
