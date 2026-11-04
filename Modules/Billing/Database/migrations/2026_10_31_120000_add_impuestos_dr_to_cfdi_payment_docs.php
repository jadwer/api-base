<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * cfdi_payment_docs.impuestos_dr: snapshot de pago20:ImpuestosDR del abono.
 *
 * Cuando la factura PPD lleva IVA, el REP debe declarar ObjetoImpDR 02 y la
 * parte proporcional de cada traslado pagada en el abono (Pagos 2.0). Se guarda
 * aqui para que el XML se regenere igual y el desglose quede auditable.
 * Forma: {"traslados": [{impuesto, tipo_factor, tasa_o_cuota, base, importe}]}
 * con base e importe en pesos a 6 decimales (string).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cfdi_payment_docs', function (Blueprint $table) {
            $table->json('impuestos_dr')->nullable()->after('objeto_imp_dr');
        });
    }

    public function down(): void
    {
        Schema::table('cfdi_payment_docs', function (Blueprint $table) {
            $table->dropColumn('impuestos_dr');
        });
    }
};
