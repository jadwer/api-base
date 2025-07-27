<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->foreignId('warehouse_location_id')->nullable()->constrained('warehouse_locations')->onDelete('set null');
            $table->decimal('quantity', 15, 4)->default(0);
            $table->decimal('reserved_quantity', 15, 4)->default(0);
            $table->decimal('available_quantity', 15, 4)->storedAs('quantity - reserved_quantity');
            $table->decimal('minimum_stock', 15, 4)->default(0);
            $table->decimal('maximum_stock', 15, 4)->nullable();
            $table->decimal('reorder_point', 15, 4)->default(0);
            $table->decimal('unit_cost', 10, 4)->default(0);
            $table->decimal('total_value', 15, 4)->storedAs('quantity * unit_cost');
            $table->enum('status', ['active', 'inactive', 'quarantine', 'damaged'])->default('active');
            $table->date('last_movement_date')->nullable();
            $table->string('last_movement_type')->nullable();
            $table->json('batch_info')->nullable(); // Para lotes específicos
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Índices
            $table->index(['product_id', 'warehouse_id']);
            $table->index(['warehouse_id', 'warehouse_location_id']);
            $table->index(['status', 'quantity']);
            $table->index('last_movement_date');
            $table->unique(['product_id', 'warehouse_id', 'warehouse_location_id'], 'unique_product_warehouse_location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock');
    }
};
