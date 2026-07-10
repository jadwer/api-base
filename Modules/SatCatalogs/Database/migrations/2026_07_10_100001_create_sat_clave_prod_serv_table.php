<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WS9 SAT catalogs: c_ClaveProdServ (~52k rows after sat:sync-catalogs).
 *
 * Global table (public SAT data, shared by all tenants). Plain index on
 * descripcion for LIKE prefix searches; no FULLTEXT because tests run on
 * SQLite.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sat_clave_prod_serv', function (Blueprint $table) {
            $table->string('clave', 10)->primary();
            $table->string('descripcion', 255);
            $table->boolean('incluye_iva')->nullable();
            $table->boolean('incluye_ieps')->nullable();
            $table->text('palabras_similares')->nullable();
            $table->date('vigencia_hasta')->nullable();

            $table->index('descripcion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sat_clave_prod_serv');
    }
};
