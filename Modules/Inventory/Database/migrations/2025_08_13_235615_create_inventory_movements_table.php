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
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            
            // Tipo y referencia del movimiento
            $table->enum('movement_type', ['entry', 'exit', 'transfer', 'adjustment']);
            $table->string('reference_type')->nullable(); // 'purchase', 'sale', 'transfer', 'adjustment', 'manual', etc.
            $table->unsignedBigInteger('reference_id')->nullable(); // ID del documento origen
            $table->dateTime('movement_date');
            $table->text('description')->nullable();
            
            // Producto y ubicación origen
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('restrict');
            $table->foreignId('warehouse_location_id')->nullable()->constrained('warehouse_locations')->onDelete('set null');
            
            // Ubicación destino (para transferencias)
            $table->foreignId('destination_warehouse_id')->nullable()->constrained('warehouses')->onDelete('restrict');
            $table->foreignId('destination_location_id')->nullable()->constrained('warehouse_locations', 'id')->onDelete('set null');
            
            // Cantidades y costos
            $table->decimal('quantity', 15, 4); // Puede ser negativo para salidas
            $table->decimal('unit_cost', 10, 4)->default(0);
            $table->decimal('total_value', 15, 4)->storedAs('quantity * unit_cost');
            
            // Estado y auditoría
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');

            // Approval workflow (consolidado)
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();

            // Quality check (consolidado)
            $table->boolean('quality_checked')->default(false);
            $table->timestamp('quality_checked_at')->nullable();
            $table->unsignedBigInteger('quality_checked_by')->nullable();
            $table->text('quality_check_notes')->nullable();

            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            
            // Stock antes y después del movimiento (para auditoría)
            $table->decimal('previous_stock', 15, 4)->nullable();
            $table->decimal('new_stock', 15, 4)->nullable();
            
            // Información adicional
            $table->json('batch_info')->nullable(); // Para lotes específicos
            $table->json('metadata')->nullable(); // Metadatos adicionales

            // GL Integration (consolidado)
            $table->bigInteger('gl_journal_entry_id')->unsigned()->nullable();
            $table->enum('gl_posting_status', ['pending', 'posted', 'error'])->default('pending');
            $table->decimal('cost_per_unit', 10, 4)->default(0.0000);
            $table->decimal('total_cost', 10, 2)->default(0.00);
            $table->text('gl_posting_notes')->nullable();

            $table->timestamps();

            // Foreign keys for approval/quality (consolidado)
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('quality_checked_by')->references('id')->on('users')->onDelete('set null');
            
            // Índices para mejorar performance
            $table->index(['movement_type', 'movement_date']);
            $table->index(['product_id', 'warehouse_id']);
            $table->index(['warehouse_id', 'movement_date']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['user_id', 'movement_date']);
            $table->index(['status', 'movement_date']);
            $table->index('gl_journal_entry_id'); // Consolidado
            $table->index('gl_posting_status'); // Consolidado
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
