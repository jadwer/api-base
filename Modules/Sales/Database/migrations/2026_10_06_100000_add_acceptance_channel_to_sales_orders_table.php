<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Autorizacion del cliente en pedidos (decision de Gabino 2026-10-06): la OC
 * deja de ser obligatoria. Se registra por que canal autorizo el cliente
 * (purchase_order, email, whatsapp, phone, counter) y customer_po_path pasa a
 * ser la constancia: PDF de la OC o captura de pantalla.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->string('acceptance_channel', 20)->nullable()->after('customer_po_number');
        });

        // Pedidos previos: todos traian numero de OC porque era obligatorio.
        DB::table('sales_orders')
            ->where('order_type', 'order')
            ->whereNotNull('customer_po_number')
            ->where('customer_po_number', '<>', '')
            ->update(['acceptance_channel' => 'purchase_order']);
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn('acceptance_channel');
        });
    }
};
