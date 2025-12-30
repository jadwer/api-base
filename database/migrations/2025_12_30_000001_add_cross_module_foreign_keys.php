<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * This migration adds foreign keys that create circular dependencies between modules.
 * These FKs are added after all tables are created to avoid migration order issues.
 *
 * Circular dependencies resolved:
 * - purchase_orders.ap_invoice_id -> ap_invoices
 * - ap_invoices.purchase_order_id -> purchase_orders
 * - sales_orders.ar_invoice_id -> ar_invoices
 * - ar_invoices.sales_order_id -> sales_orders
 * - sales_orders.checkout_session_id -> checkout_sessions
 */
return new class extends Migration
{
    public function up(): void
    {
        // Purchase Orders -> AP Invoices
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreign('ap_invoice_id')
                ->references('id')
                ->on('ap_invoices')
                ->onDelete('set null');
        });

        // AP Invoices -> Purchase Orders
        Schema::table('ap_invoices', function (Blueprint $table) {
            $table->foreign('purchase_order_id')
                ->references('id')
                ->on('purchase_orders')
                ->onDelete('restrict');
        });

        // Sales Orders -> AR Invoices
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->foreign('ar_invoice_id')
                ->references('id')
                ->on('ar_invoices')
                ->onDelete('set null');
        });

        // AR Invoices -> Sales Orders
        Schema::table('ar_invoices', function (Blueprint $table) {
            $table->foreign('sales_order_id')
                ->references('id')
                ->on('sales_orders')
                ->onDelete('restrict');
        });

        // Sales Orders -> Checkout Sessions (Ecommerce)
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->foreign('checkout_session_id')
                ->references('id')
                ->on('checkout_sessions')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropForeign(['checkout_session_id']);
        });

        Schema::table('ar_invoices', function (Blueprint $table) {
            $table->dropForeign(['sales_order_id']);
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropForeign(['ar_invoice_id']);
        });

        Schema::table('ap_invoices', function (Blueprint $table) {
            $table->dropForeign(['purchase_order_id']);
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['ap_invoice_id']);
        });
    }
};
