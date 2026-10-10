<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * P2 granel: tipo de precio del producto.
 *
 * fixed      = precio de lista (comportamiento de siempre).
 * quote_only = "Precio sujeto a cotizacion": sin precio de lista; se cotiza y
 *              se vende solo via cotizacion convertida a pedido.
 *
 * products.price ya es nullable desde la migracion original, no se toca.
 *
 * Datos: los productos que hoy tienen price NULL quedan como quote_only en la
 * misma migracion (antes eran un dato silenciosamente malo: el carrito los
 * cobraba en 0 y el PDF publico los omitia). Para saber cuantos son antes de
 * desplegar: SELECT COUNT(*) FROM products WHERE price IS NULL;
 *
 * Rollback: quitar la columna. No se reescribe price en ningun sentido, asi que
 * bajar la migracion deja cada producto exactamente como estaba antes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('pricing_type', 20)->default('fixed')->after('price');
            $table->index('pricing_type');
        });

        DB::table('products')
            ->whereNull('price')
            ->update(['pricing_type' => 'quote_only']);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['pricing_type']);
            $table->dropColumn('pricing_type');
        });
    }
};
