<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * cfdi_payment_docs: the pago20:DoctoRelacionado nodes of a REP.
 *
 * Each row documents one invoice covered by a payment inside a CFDI tipo P.
 * v1 always creates exactly one row per REP (one abono to one PPD invoice);
 * the table shape already supports the v2 multi-factura case.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cfdi_payment_docs', function (Blueprint $table) {
            $table->id();

            // FK to the CFDI tipo P that owns this DoctoRelacionado
            $table->foreignId('payment_cfdi_id')
                ->constrained('cfdi_invoices')
                ->onDelete('cascade');

            // The related PPD invoice being paid
            $table->string('related_uuid', 36); // UUID de la factura PPD timbrada (IdDocumento)
            $table->string('serie', 25)->nullable();
            $table->string('folio', 40)->nullable();
            $table->string('moneda_dr', 3)->default('MXN'); // MonedaDR

            // Amounts (stored as integers in cents, same convention as cfdi_invoices)
            $table->unsignedInteger('num_parcialidad'); // NumParcialidad
            $table->unsignedBigInteger('imp_saldo_ant'); // ImpSaldoAnt (saldo antes del abono)
            $table->unsignedBigInteger('imp_pagado'); // ImpPagado (monto del abono)
            $table->unsignedBigInteger('imp_saldo_insoluto'); // ImpSaldoInsoluto (saldo despues)

            $table->string('objeto_imp_dr', 2)->default('01'); // ObjetoImpDR (01=No objeto de impuesto)

            $table->timestamps();

            $table->index('payment_cfdi_id');
            $table->index('related_uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cfdi_payment_docs');
    }
};
