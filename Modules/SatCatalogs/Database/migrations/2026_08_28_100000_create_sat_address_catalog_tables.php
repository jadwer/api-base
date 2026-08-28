<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catalogos de domicilio del SAT (CFDI 4.0) para autollenar direcciones
 * a partir del codigo postal (peticion cliente 2026-08-25).
 *
 * Denormalizados a proposito: el lookup por CP no hace joins y las listas
 * en cascada (estado -> municipio) salen con DISTINCT de la misma tabla.
 * Fuente: phpcfdi/resources-sat-catalogs (cfdi_40_codigos_postales,
 * cfdi_40_colonias + nombres de cfdi_40_estados / cfdi_40_municipios).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sat_codigos_postales', function (Blueprint $table) {
            $table->string('codigo_postal', 5)->primary();
            $table->string('estado_clave', 3);
            $table->string('estado', 40);
            $table->string('municipio_clave', 3)->nullable();
            $table->string('municipio', 60)->nullable();
            $table->string('localidad_clave', 2)->nullable();

            $table->index(['estado_clave', 'municipio_clave']);
        });

        Schema::create('sat_colonias', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_postal', 5);
            $table->string('clave', 4);
            $table->string('nombre', 100);

            $table->unique(['codigo_postal', 'clave']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sat_colonias');
        Schema::dropIfExists('sat_codigos_postales');
    }
};
