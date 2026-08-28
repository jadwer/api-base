<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campos de direccion como los pide el SAT (nodo Domicilio de CFDI:
 * Calle, NumeroExterior, NumeroInterior, Colonia, Referencia, Municipio,
 * Estado, Pais, CodigoPostal). Peticion cliente 2026-08-25.
 *
 * Todos nullable: las direcciones legadas viven en address_line_1/2 y se
 * muestran tal cual hasta que se editen. El SAT no tiene campos manzana/
 * lote: "Mz 3 Lt 3" se captura como texto libre en calle o numero
 * exterior, por eso son strings laxos y JAMAS validacion numerica.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_addresses', function (Blueprint $table) {
            // city era NOT NULL; con el modelo SAT la localidad/ciudad es
            // opcional (municipio la cubre), y una direccion solo-SAT sin
            // city tronaba con 500 a nivel DB.
            $table->string('city')->nullable()->change();

            $table->string('street')->nullable()->after('address_line_2');
            $table->string('exterior_number', 50)->nullable()->after('street');
            $table->string('interior_number', 50)->nullable()->after('exterior_number');
            $table->string('neighborhood')->nullable()->after('interior_number');
            $table->string('municipality')->nullable()->after('neighborhood');
            $table->string('reference')->nullable()->after('postal_code');
        });
    }

    public function down(): void
    {
        Schema::table('contact_addresses', function (Blueprint $table) {
            $table->dropColumn([
                'street', 'exterior_number', 'interior_number',
                'neighborhood', 'municipality', 'reference',
            ]);
        });
    }
};
