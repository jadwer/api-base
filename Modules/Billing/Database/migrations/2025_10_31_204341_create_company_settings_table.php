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
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();

            // Company Fiscal Information
            $table->string('company_name'); // Razón Social
            $table->string('rfc', 13)->unique(); // RFC (12-13 caracteres)
            $table->string('tax_regime', 10); // Régimen Fiscal (e.g., "612")
            $table->string('postal_code', 5); // Código Postal

            // Invoice Series & Folios
            $table->string('invoice_series', 10)->default('F'); // Serie para Facturas
            $table->string('credit_note_series', 10)->default('N'); // Serie para Notas de Crédito
            $table->unsignedInteger('next_invoice_folio')->default(1); // Siguiente folio de factura
            $table->unsignedInteger('next_credit_note_folio')->default(1); // Siguiente folio de nota

            // PAC (Proveedor Autorizado de Certificación) Configuration
            $table->string('pac_provider')->nullable(); // Nombre del PAC (e.g., "Finkok", "SW")
            $table->string('pac_username')->nullable(); // Usuario del PAC
            $table->text('pac_password')->nullable(); // Password del PAC (encrypted)
            $table->boolean('pac_production_mode')->default(false); // false = pruebas, true = producción

            // Digital Certificate (.cer y .key del SAT)
            $table->string('certificate_file')->nullable(); // Path al archivo .cer
            $table->string('key_file')->nullable(); // Path al archivo .key
            $table->text('key_password')->nullable(); // Password de la llave privada (encrypted)

            // Additional Settings
            $table->string('logo_path')->nullable(); // Path al logo de la empresa
            $table->json('additional_settings')->nullable(); // Configuraciones adicionales

            // Status
            $table->boolean('is_active')->default(true); // Solo una configuración puede estar activa
            $table->timestamps();

            // Indexes
            $table->index('rfc');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};
