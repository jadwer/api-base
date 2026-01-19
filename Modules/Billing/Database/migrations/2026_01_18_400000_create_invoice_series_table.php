<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * SA-M011: Support for multiple invoice series (FAC, FAC-W, REFAC, etc.)
     */
    public function up(): void
    {
        Schema::create('invoice_series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_setting_id')->constrained()->onDelete('cascade');

            // Series identification
            $table->string('code', 10); // FAC, FAC-W, REFAC, N, etc.
            $table->string('name'); // "Factura Normal", "Factura Web", "Refactura", "Nota de Credito"
            $table->text('description')->nullable();

            // Type of CFDI this series is for
            $table->enum('cfdi_type', ['I', 'E', 'P', 'N', 'T'])->default('I');
            // I = Ingreso (Invoice)
            // E = Egreso (Credit Note)
            // P = Pago (Payment)
            // N = Nomina (Payroll)
            // T = Traslado (Transfer)

            // Folio tracking
            $table->unsignedInteger('current_folio')->default(0);
            $table->unsignedInteger('initial_folio')->default(1);
            $table->unsignedInteger('folio_padding')->default(6); // Number of digits

            // Format options
            $table->boolean('include_year')->default(false);
            $table->string('year_format', 4)->default('y'); // y = 26, Y = 2026
            $table->string('separator', 2)->default('-'); // FAC-000001 or FAC000001

            // Behavior
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false); // Default series for this CFDI type
            $table->boolean('reset_yearly')->default(false); // Reset folio on Jan 1
            $table->unsignedSmallInteger('last_reset_year')->nullable();

            // Restrictions
            $table->string('allowed_roles')->nullable(); // JSON array of roles that can use this series
            $table->string('source_type')->nullable(); // 'web', 'pos', 'manual', null = any

            $table->timestamps();

            // Indexes
            $table->unique(['company_setting_id', 'code']);
            $table->index(['company_setting_id', 'cfdi_type', 'is_default']);
            $table->index(['is_active', 'cfdi_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_series');
    }
};
