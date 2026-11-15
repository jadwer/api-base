<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fecha estimada de llegada (ETA) de la orden de compra (junta con el cliente
 * 2026-11-12, pedido J1): la da el proveedor en su cotizacion o al momento de
 * comprar. Sirve para saber que viene atrasado en "Por recibir".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->date('expected_arrival_date')->nullable()->after('order_date')->index();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropIndex(['expected_arrival_date']);
            $table->dropColumn('expected_arrival_date');
        });
    }
};
