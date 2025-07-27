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
        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->foreignId('warehouse_location_id')->nullable()->constrained('warehouse_locations')->onDelete('set null');
            $table->string('batch_number')->unique();
            $table->string('lot_number')->nullable();
            $table->date('manufacturing_date')->nullable();
            $table->date('expiration_date')->nullable();
            $table->date('best_before_date')->nullable();
            $table->decimal('initial_quantity', 15, 4);
            $table->decimal('current_quantity', 15, 4);
            $table->decimal('reserved_quantity', 15, 4)->default(0);
            $table->decimal('available_quantity', 15, 4)->storedAs('current_quantity - reserved_quantity');
            $table->decimal('unit_cost', 10, 4)->default(0);
            $table->decimal('total_value', 15, 4)->storedAs('current_quantity * unit_cost');
            $table->enum('status', ['active', 'expired', 'quarantine', 'recalled', 'consumed'])->default('active');
            $table->string('supplier_name')->nullable();
            $table->string('supplier_batch')->nullable();
            $table->text('quality_notes')->nullable();
            $table->json('test_results')->nullable();
            $table->json('certifications')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Índices
            $table->index(['product_id', 'warehouse_id']);
            $table->index(['warehouse_id', 'warehouse_location_id']);
            $table->index(['status', 'expiration_date']);
            $table->index('batch_number');
            $table->index('lot_number');
            $table->index('expiration_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_batches');
    }
};
