<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * IV-M003: Lot Traceability - Add direct FK relationship between InventoryMovement and ProductBatch
 *
 * This migration adds a proper foreign key relationship to replace the loose JSON batch_info field,
 * enabling true lot traceability queries like "all movements for batch X".
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            // Add product_batch_id FK after warehouse_location_id
            $table->foreignId('product_batch_id')
                ->nullable()
                ->after('warehouse_location_id')
                ->constrained('product_batches')
                ->onDelete('set null');

            // Add destination_batch_id for transfer movements (split/consolidation)
            $table->foreignId('destination_batch_id')
                ->nullable()
                ->after('destination_location_id')
                ->constrained('product_batches')
                ->onDelete('set null');

            // Index for batch traceability queries
            $table->index('product_batch_id');
            $table->index('destination_batch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropForeign(['product_batch_id']);
            $table->dropForeign(['destination_batch_id']);
            $table->dropIndex(['product_batch_id']);
            $table->dropIndex(['destination_batch_id']);
            $table->dropColumn(['product_batch_id', 'destination_batch_id']);
        });
    }
};
