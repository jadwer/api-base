<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SA-M001: Shipments table for partial shipment support.
 *
 * A SalesOrder can have multiple Shipments, each containing
 * a subset of items with specific quantities.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')
                ->constrained('sales_orders')
                ->onDelete('cascade');
            $table->foreignId('warehouse_id')
                ->nullable()
                ->constrained('warehouses')
                ->onDelete('set null');
            $table->string('shipment_number', 50)->unique();
            $table->enum('status', [
                'pending',      // Shipment created, not yet shipped
                'processing',   // Being prepared
                'shipped',      // In transit
                'delivered',    // Delivered to customer
                'cancelled',    // Cancelled shipment
                'returned',     // Returned shipment
            ])->default('pending');
            $table->string('carrier', 100)->nullable();        // FedEx, UPS, DHL, etc.
            $table->string('tracking_number', 100)->nullable();
            $table->string('tracking_url', 500)->nullable();
            $table->date('ship_date')->nullable();             // When shipped
            $table->date('estimated_delivery')->nullable();
            $table->date('actual_delivery')->nullable();
            $table->text('shipping_address')->nullable();      // Override order address
            $table->text('notes')->nullable();
            $table->float('shipping_cost')->default(0);
            $table->float('weight')->nullable();               // Package weight
            $table->json('metadata')->nullable();              // Additional data
            $table->timestamps();

            $table->index('sales_order_id');
            $table->index('shipment_number');
            $table->index('status');
            $table->index('tracking_number');
            $table->index('ship_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
