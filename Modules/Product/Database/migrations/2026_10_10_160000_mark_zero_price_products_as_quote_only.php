<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * P2b (D8): ningun producto queda vendible en $0. La migracion de pricing_type
 * marco quote_only los productos con price NULL; esta completa la regla con
 * los de precio fijo y price <= 0 (en prod al 2026-10-10 son 0, pero dev o
 * una carga directa pueden tenerlos) y vuelve a cubrir los NULL por si la
 * anterior ya corrio antes de una carga.
 *
 * Es idempotente: solo toca filas fixed sin precio vendible. Se usa query
 * builder (sin observers) y no reescribe price: el observer lo limpia en el
 * siguiente guardado.
 *
 * Conteo previo (solo lectura):
 * SELECT COUNT(*) FROM products WHERE pricing_type = 'fixed' AND (price IS NULL OR price <= 0);
 *
 * Rollback: no hace nada. No se puede saber cuales eran fixed antes; dejarlos
 * como sujetos a cotizacion es el estado seguro.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')
            ->where('pricing_type', 'fixed')
            ->where(fn ($q) => $q->whereNull('price')->orWhere('price', '<=', 0))
            ->update(['pricing_type' => 'quote_only']);
    }

    public function down(): void
    {
        // Sin reversa a proposito (ver arriba).
    }
};
