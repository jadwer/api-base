<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Todos los productos llevan IVA 16% (solicitud directa de Jasim 2026-10-22):
 * los precios capturados son sin IVA y solo 31 de ~39,900 tenian la bandera,
 * asi que la tienda no mostraba "+ IVA" y cotizaciones, pedidos y CFDI
 * calculaban 0%. Los productos nuevos nacen con IVA.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')->update(['iva' => true, 'tax_rate' => 16]);

        Schema::table('products', function (Blueprint $table) {
            $table->boolean('iva')->default(true)->change();
        });
    }

    public function down(): void
    {
        // Los datos no se revierten (no hay forma de saber cuales eran los 31
        // originales sin el dump); solo el default de la columna.
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('iva')->default(false)->change();
        });
    }
};
