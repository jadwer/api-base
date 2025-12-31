<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SA-M001: Add shipment tracking fields to sales_order_items.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_order_items', function (Blueprint $table) {
            $table->float('shipped_quantity')->default(0)->after('quantity');
            $table->enum('fulfillment_status', [
                'pending',           // Not shipped yet
                'partially_shipped', // Some quantity shipped
                'shipped',           // All quantity shipped
                'delivered',         // Confirmed delivered
            ])->default('pending')->after('shipped_quantity');

            $table->index('fulfillment_status');
        });
    }

    public function down(): void
    {
        Schema::table('sales_order_items', function (Blueprint $table) {
            $table->dropIndex(['fulfillment_status']);
            $table->dropColumn(['shipped_quantity', 'fulfillment_status']);
        });
    }
};
