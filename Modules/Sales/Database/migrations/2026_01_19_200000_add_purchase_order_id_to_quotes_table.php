<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add purchase_order_id to quotes table for Quote -> PurchaseOrder relationship.
     * Phase 12: Quote can generate a PurchaseOrder to request products from suppliers.
     */
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->foreignId('purchase_order_id')
                ->nullable()
                ->after('sales_order_id')
                ->constrained('purchase_orders')
                ->onDelete('set null');

            $table->index('purchase_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropForeign(['purchase_order_id']);
            $table->dropColumn('purchase_order_id');
        });
    }
};
