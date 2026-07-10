<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * WS9: SAT fields on products.
 *
 * - sat_clave_prod_serv / sat_clave_unidad: logical FKs to the SAT catalogs
 *   (no DB constraint: the catalogs may be empty on fresh installs until
 *   sat:sync-catalogs runs).
 * - product_type: finished | raw_material | both.
 * - tax_rate: percentage (16 = 16%). NULL means "Exento" (fiscally different
 *   from 0%). Existing rows are backfilled from the legacy iva boolean, which
 *   is kept for public catalog compatibility.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('sat_clave_prod_serv', 10)->nullable()->after('iva');
            $table->string('sat_clave_unidad', 20)->nullable()->after('sat_clave_prod_serv');
            $table->string('product_type')->nullable()->after('sat_clave_unidad');
            $table->decimal('tax_rate', 5, 2)->nullable()->after('product_type');
        });

        // Backfill from the legacy iva boolean (existing data was never Exento).
        DB::table('products')->where('iva', true)->update(['tax_rate' => 16]);
        DB::table('products')->where('iva', false)->update(['tax_rate' => 0]);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['sat_clave_prod_serv', 'sat_clave_unidad', 'product_type', 'tax_rate']);
        });
    }
};
