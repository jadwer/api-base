<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WS9 SAT catalogs: c_FormaPago (22 rows in the full catalog).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sat_forma_pago', function (Blueprint $table) {
            $table->string('clave', 2)->primary();
            $table->string('descripcion', 100);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sat_forma_pago');
    }
};
