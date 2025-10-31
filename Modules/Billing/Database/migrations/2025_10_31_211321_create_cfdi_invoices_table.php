<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cfdi_invoices', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('company_setting_id')->constrained('company_settings')->onDelete('restrict');
            $table->foreignId('contact_id')->constrained('contacts')->onDelete('restrict'); // Customer (Party Pattern)
            $table->foreignId('ar_invoice_id')->nullable()->constrained('ar_invoices')->onDelete('restrict'); // Finance module

            // CFDI Identification
            $table->string('series', 10); // Serie (F, A, B, etc)
            $table->unsignedInteger('folio'); // Folio number
            $table->string('uuid', 36)->nullable()->unique(); // UUID del SAT (after timbrado)
            $table->string('tipo_comprobante', 2)->default('I'); // I=Ingreso, E=Egreso, T=Traslado, N=Nómina, P=Pago

            // Customer (Receptor) Information
            $table->string('receptor_rfc', 13);
            $table->string('receptor_nombre', 255);
            $table->string('receptor_uso_cfdi', 10)->default('G03'); // G03=Gastos en general
            $table->string('receptor_regimen_fiscal', 10)->nullable();
            $table->string('receptor_domicilio_fiscal', 5)->nullable(); // Código postal

            // Amounts (stored as integers in cents/centavos)
            $table->unsignedBigInteger('subtotal'); // Before taxes
            $table->unsignedBigInteger('total'); // After taxes
            $table->unsignedBigInteger('descuento')->default(0); // Discount
            $table->unsignedBigInteger('iva')->default(0); // IVA amount
            $table->unsignedBigInteger('ieps')->default(0); // IEPS amount
            $table->unsignedBigInteger('isr_retenido')->default(0); // ISR retained
            $table->unsignedBigInteger('iva_retenido')->default(0); // IVA retained

            // Currency
            $table->string('moneda', 3)->default('MXN'); // ISO 4217
            $table->decimal('tipo_cambio', 10, 6)->default(1.000000); // Exchange rate

            // Payment Information
            $table->string('forma_pago', 2)->nullable(); // 01=Efectivo, 03=Transferencia, etc
            $table->string('metodo_pago', 3)->default('PUE'); // PUE=Pago en una sola exhibición, PPD=Pago en parcialidades
            $table->string('condiciones_pago')->nullable(); // Payment terms text

            // Related CFDI (for credit notes, etc)
            $table->string('cfdi_relacionado_tipo')->nullable(); // 01=Nota de crédito, 02=Nota de débito, etc
            $table->json('cfdi_relacionado_uuids')->nullable(); // Array of related UUIDs

            // Status
            $table->string('status', 20)->default('draft'); // draft, valid, cancelled, error

            // Dates
            $table->timestamp('fecha_emision'); // Issue date
            $table->timestamp('fecha_timbrado')->nullable(); // Stamping date
            $table->timestamp('fecha_cancelacion')->nullable(); // Cancellation date

            // Files
            $table->string('xml_path')->nullable(); // Path to XML file
            $table->string('pdf_path')->nullable(); // Path to PDF file

            // PAC Response
            $table->text('pac_response')->nullable(); // JSON response from PAC
            $table->text('error_message')->nullable(); // Error message if timbrado failed

            // Additional data
            $table->json('metadata')->nullable(); // Additional metadata

            $table->timestamps();

            // Indexes
            $table->index('company_setting_id');
            $table->index('contact_id');
            $table->index('ar_invoice_id');
            $table->index(['series', 'folio']);
            $table->index('uuid');
            $table->index('status');
            $table->index('fecha_emision');
            $table->unique(['company_setting_id', 'series', 'folio']); // Unique folio per company+series
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cfdi_invoices');
    }
};
