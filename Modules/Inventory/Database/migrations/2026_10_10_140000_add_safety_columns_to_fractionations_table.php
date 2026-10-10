<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P3 granel (2026-10-10): fraccionamiento seguro.
 *
 * - idempotency_key: clave que genera el formulario; UNIQUE nullable para que
 *   un doble POST (doble clic, reintento de red) no fraccione dos veces. Los
 *   registros previos quedan en NULL (varios NULL no violan el unique).
 * - requested_quantity: lo que el usuario pidio fraccionar; source_quantity
 *   pasa a ser lo que realmente salio (con piezas enteras puede ser menos).
 * - warehouse_location_id, source_batch_id, destination_batch_id: ubicacion y
 *   trazabilidad de lote origen -> lote destino.
 * - source_unit_cost, destination_unit_cost, total_cost: costo con la merma
 *   absorbida en el destino.
 * - conversion_factor_used a 6 decimales (galon = 0.264172).
 *
 * Rollback: quita las columnas y regresa el factor a 4 decimales.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fractionations', function (Blueprint $table) {
            $table->string('idempotency_key', 64)->nullable()->after('folio_number');
            $table->unique('idempotency_key', 'fractionations_idempotency_key_unique');
            $table->decimal('requested_quantity', 15, 4)->nullable()->after('source_quantity');
            $table->foreignId('warehouse_location_id')->nullable()->after('warehouse_id')
                ->constrained('warehouse_locations')->nullOnDelete();
            $table->foreignId('source_batch_id')->nullable()->after('warehouse_location_id')
                ->constrained('product_batches')->nullOnDelete();
            $table->foreignId('destination_batch_id')->nullable()->after('source_batch_id')
                ->constrained('product_batches')->nullOnDelete();
            $table->decimal('source_unit_cost', 15, 6)->default(0)->after('conversion_factor_used');
            $table->decimal('destination_unit_cost', 15, 6)->default(0)->after('source_unit_cost');
            $table->decimal('total_cost', 15, 4)->default(0)->after('destination_unit_cost');
        });

        Schema::table('fractionations', function (Blueprint $table) {
            $table->decimal('conversion_factor_used', 15, 6)->change();
        });
    }

    public function down(): void
    {
        Schema::table('fractionations', function (Blueprint $table) {
            $table->dropUnique('fractionations_idempotency_key_unique');
            $table->dropConstrainedForeignId('destination_batch_id');
            $table->dropConstrainedForeignId('source_batch_id');
            $table->dropConstrainedForeignId('warehouse_location_id');
            $table->dropColumn([
                'idempotency_key',
                'requested_quantity',
                'source_unit_cost',
                'destination_unit_cost',
                'total_cost',
            ]);
        });

        Schema::table('fractionations', function (Blueprint $table) {
            $table->decimal('conversion_factor_used', 15, 4)->change();
        });
    }
};
