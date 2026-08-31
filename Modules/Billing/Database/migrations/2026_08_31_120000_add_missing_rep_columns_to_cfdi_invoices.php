<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Paquete B: el Schema JSON:API y el REPService usan num_parcialidad e
 * imp_saldo_insoluto desde el diseno del Complemento de Pagos, pero la
 * migracion nunca las creo; sin fillable el create las ignoraba en
 * silencio y la cadena entera (persistencia -> Resource -> XML) quedo
 * coja sin que ningun test lo notara.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cfdi_invoices', function (Blueprint $table) {
            $table->unsignedInteger('num_parcialidad')->nullable()->after('forma_pago_p');
            $table->bigInteger('imp_saldo_insoluto')->nullable()->after('num_parcialidad'); // centavos
        });
    }

    public function down(): void
    {
        Schema::table('cfdi_invoices', function (Blueprint $table) {
            $table->dropColumn(['num_parcialidad', 'imp_saldo_insoluto']);
        });
    }
};
