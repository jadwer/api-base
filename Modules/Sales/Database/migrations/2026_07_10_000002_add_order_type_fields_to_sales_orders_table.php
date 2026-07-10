<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fase A - Venta directa vs Pedido.
     * order_type: direct_sale (mostrador, presupone stock) | order (pedido, proceso completo).
     * customer_po_number/customer_po_path: orden de compra del cliente (requerida en pedidos).
     */
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->string('order_type', 20)->default('order')->after('status'); // direct_sale | order
            $table->string('customer_po_number', 100)->nullable()->after('order_type');
            $table->string('customer_po_path')->nullable()->after('customer_po_number');
            $table->string('payment_method', 3)->nullable()->after('customer_po_path'); // PPD | PUE
            $table->unsignedSmallInteger('credit_days')->nullable()->after('payment_method');

            $table->index('order_type');
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropIndex(['order_type']);
            $table->dropColumn([
                'order_type', 'customer_po_number', 'customer_po_path',
                'payment_method', 'credit_days',
            ]);
        });
    }
};
