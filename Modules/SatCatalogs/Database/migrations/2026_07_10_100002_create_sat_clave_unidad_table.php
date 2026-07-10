<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WS9 SAT catalogs: c_ClaveUnidad (~2.4k rows after sat:sync-catalogs).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sat_clave_unidad', function (Blueprint $table) {
            $table->string('clave', 20)->primary();
            $table->string('nombre', 255);
            $table->text('descripcion')->nullable();
            $table->string('simbolo', 30)->nullable();

            $table->index('nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sat_clave_unidad');
    }
};
