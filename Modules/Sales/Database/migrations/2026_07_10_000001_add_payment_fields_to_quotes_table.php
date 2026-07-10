<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fase A - Venta directa vs Pedido.
     * payment_method (PPD|PUE) y credit_days viajan de la cotizacion a la orden al convertir.
     */
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->string('payment_method', 3)->nullable()->after('currency'); // PPD | PUE
            $table->unsignedSmallInteger('credit_days')->nullable()->after('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'credit_days']);
        });
    }
};
