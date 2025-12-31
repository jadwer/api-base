<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SA-M002: Backorders table for managing items with insufficient stock.
 *
 * When a sales order item cannot be fully fulfilled due to stock shortage,
 * a backorder is created to track the pending quantity.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backorders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')
                ->constrained('sales_orders')
                ->onDelete('cascade');
            $table->foreignId('sales_order_item_id')
                ->constrained('sales_order_items')
                ->onDelete('cascade');
            $table->foreignId('product_id')
                ->constrained('products')
                ->onDelete('restrict');
            $table->foreignId('warehouse_id')
                ->nullable()
                ->constrained('warehouses')
                ->onDelete('set null');
            $table->string('backorder_number', 50)->unique();
            $table->float('original_quantity');         // Cantidad original solicitada
            $table->float('backorder_quantity');        // Cantidad pendiente
            $table->float('fulfilled_quantity')->default(0); // Cantidad ya cumplida
            $table->enum('status', [
                'pending',          // Esperando stock
                'partially_fulfilled', // Parte ya enviada
                'fulfilled',        // Completamente cumplido
                'cancelled',        // Cancelado por cliente/admin
            ])->default('pending');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->date('expected_date')->nullable();  // Fecha esperada de stock
            $table->date('promised_date')->nullable();  // Fecha prometida al cliente
            $table->text('notes')->nullable();
            $table->text('customer_notes')->nullable(); // Notas visibles al cliente
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('sales_order_id');
            $table->index('sales_order_item_id');
            $table->index('product_id');
            $table->index('warehouse_id');
            $table->index('status');
            $table->index('priority');
            $table->index('expected_date');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backorders');
    }
};
