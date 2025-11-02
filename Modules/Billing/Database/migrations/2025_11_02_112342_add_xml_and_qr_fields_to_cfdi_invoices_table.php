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
        Schema::table('cfdi_invoices', function (Blueprint $table) {
            // XML Content Storage (for SAT compliance and auditing)
            $table->longText('xml_original')->nullable()->after('xml_path'); // Original XML before timbrado
            $table->longText('xml_timbrado')->nullable()->after('xml_original'); // Stamped XML from PAC

            // QR Code Storage (for PDF generation)
            $table->text('qr_code')->nullable()->after('pdf_path'); // QR code data URI
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cfdi_invoices', function (Blueprint $table) {
            $table->dropColumn(['xml_original', 'xml_timbrado', 'qr_code']);
        });
    }
};
