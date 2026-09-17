<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // SEO Bloque 1b (2026-09): URL legible y estable por producto
            // (/productos/<slug>). Nullable para poder migrar en caliente: el
            // comando products:backfill-slugs rellena los existentes por lotes y
            // el ProductObserver genera el de los nuevos. Unico: la colision se
            // resuelve con sufijo numerico (ver Support\ProductSlug).
            $table->string('slug', 191)->nullable()->after('sku');
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
