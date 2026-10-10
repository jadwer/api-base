<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P3 granel (2026-10-10):
 * - conversion_factor a 6 decimales: 1 L a galon es 0.264172; con 4 decimales
 *   20,000 L daban 5,284 galones en vez de 5,283.4.
 * - whole_units: la conversion produce solo piezas completas. Default false
 *   para no cambiar el resultado de las conversiones que ya existen.
 *
 * Rollback: quita whole_units y regresa el factor a 4 decimales (redondea).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_conversions', function (Blueprint $table) {
            $table->decimal('conversion_factor', 15, 6)->change();
        });

        Schema::table('product_conversions', function (Blueprint $table) {
            $table->boolean('whole_units')->default(false)->after('waste_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('product_conversions', function (Blueprint $table) {
            $table->dropColumn('whole_units');
        });

        Schema::table('product_conversions', function (Blueprint $table) {
            $table->decimal('conversion_factor', 15, 4)->change();
        });
    }
};
