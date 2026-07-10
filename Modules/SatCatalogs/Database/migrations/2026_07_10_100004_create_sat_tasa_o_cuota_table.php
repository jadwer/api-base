<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WS9 SAT catalogs: c_TasaOCuota (concrete selectable rates).
 *
 * tipo = Tasa | Cuota | Exento; valor is null for Exento rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sat_tasa_o_cuota', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 10); // Tasa | Cuota | Exento
            $table->string('impuesto', 10); // IVA | ISR | IEPS
            $table->decimal('valor', 7, 6)->nullable();
            $table->boolean('retencion')->default(false);
            $table->boolean('traslado')->default(false);

            $table->index(['impuesto', 'traslado', 'retencion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sat_tasa_o_cuota');
    }
};
