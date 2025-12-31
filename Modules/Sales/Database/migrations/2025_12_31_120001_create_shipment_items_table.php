<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SA-M001: Shipment Items table.
 *
 * Links shipments to sales order items with quantities.
 * Allows partial fulfillment of line items across multiple shipments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')
                ->constrained('shipments')
                ->onDelete('cascade');
            $table->foreignId('sales_order_item_id')
                ->constrained('sales_order_items')
                ->onDelete('cascade');
            $table->float('quantity');                        // Quantity in this shipment
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['shipment_id', 'sales_order_item_id'], 'shipment_item_unique');
            $table->index('shipment_id');
            $table->index('sales_order_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_items');
    }
};
