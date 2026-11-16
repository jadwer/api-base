<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Direccion desglosada del almacen (rediseno de Inventario, decision de Gabino
 * 2026-11-15): calle, numeros, colonia, municipio y referencia, ademas de la
 * ciudad, estado, pais y CP que ya habia. Deja el domicilio listo para la Carta
 * Porte. La direccion libre (address) se conserva para lo capturado antes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->string('street')->nullable()->after('address');
            $table->string('exterior_number', 30)->nullable()->after('street');
            $table->string('interior_number', 30)->nullable()->after('exterior_number');
            $table->string('neighborhood')->nullable()->after('interior_number');
            $table->string('municipality')->nullable()->after('neighborhood');
            $table->string('address_reference')->nullable()->after('municipality');
        });
    }

    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropColumn(['street', 'exterior_number', 'interior_number', 'neighborhood', 'municipality', 'address_reference']);
        });
    }
};
