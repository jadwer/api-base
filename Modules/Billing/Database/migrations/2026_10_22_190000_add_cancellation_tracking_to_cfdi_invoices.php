<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cancelacion de CFDI con aceptacion del receptor (junta 2026-10-08, estilo
 * Bind): la factura queda en 'cancel_pending' hasta que el SAT la da por
 * cancelada o el receptor la rechaza. Se guarda el estatus que reporta el
 * SAT y cuando se pidio y se consulto por ultima vez.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cfdi_invoices', function (Blueprint $table) {
            $table->string('motivo_cancelacion', 2)->nullable()->after('fecha_cancelacion');
            $table->string('uuid_sustitucion', 36)->nullable()->after('motivo_cancelacion');
            $table->string('cancellation_status', 60)->nullable()->after('uuid_sustitucion');
            $table->timestamp('cancellation_requested_at')->nullable()->after('cancellation_status');
            $table->timestamp('cancellation_checked_at')->nullable()->after('cancellation_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('cfdi_invoices', function (Blueprint $table) {
            $table->dropColumn([
                'motivo_cancelacion',
                'uuid_sustitucion',
                'cancellation_status',
                'cancellation_requested_at',
                'cancellation_checked_at',
            ]);
        });
    }
};
