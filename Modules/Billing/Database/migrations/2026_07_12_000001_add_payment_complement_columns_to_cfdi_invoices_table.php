<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Complemento de Pagos 2.0 (REP): payment-specific columns on cfdi_invoices.
 *
 * A CFDI tipo P reuses the cfdi_invoices table (no new table for the comprobante).
 * These columns hold the real payment data (pago20:Pago node) plus the link to
 * the Finance abono (ar_payment_id) that guarantees idempotency: one REP per abono.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cfdi_invoices', function (Blueprint $table) {
            // Real payment data for the pago20:Pago node (only used when tipo_comprobante='P')
            $table->timestamp('fecha_pago')->nullable()->after('metodo_pago'); // FechaPago del abono
            $table->unsignedBigInteger('monto_pago')->nullable()->after('fecha_pago'); // Monto del abono, en centavos
            $table->string('forma_pago_p', 2)->nullable()->after('monto_pago'); // Forma de pago REAL del abono (01, 03, ...)

            // Link to the Finance abono. UNIQUE => idempotency (one REP per abono).
            $table->unsignedBigInteger('ar_payment_id')->nullable()->after('ar_invoice_id');
            $table->unique('ar_payment_id', 'cfdi_invoices_ar_payment_id_unique');
            $table->index('ar_payment_id');
        });

        // CFDI tipo P carries no MetodoPago at the comprobante level, so the
        // column must accept null (it was NOT NULL default PUE for tipo I).
        Schema::table('cfdi_invoices', function (Blueprint $table) {
            $table->string('metodo_pago', 3)->nullable()->default('PUE')->change();
        });
    }

    public function down(): void
    {
        Schema::table('cfdi_invoices', function (Blueprint $table) {
            $table->dropUnique('cfdi_invoices_ar_payment_id_unique');
            $table->dropIndex(['ar_payment_id']);
            $table->dropColumn(['fecha_pago', 'monto_pago', 'forma_pago_p', 'ar_payment_id']);
        });
    }
};
