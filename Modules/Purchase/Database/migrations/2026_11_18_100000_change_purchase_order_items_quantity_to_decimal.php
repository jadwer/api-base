<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La cantidad de la partida de compra era integer: una compra a granel (2.5 L) se guardaba
 * como 3 mientras el subtotal se calculaba con 2.5 y la recepcion (decimal) aceptaba 2.5.
 * Pasa a decimal(15,4), igual que received_quantity e invoiced_quantity. Revision de
 * AtomoPlatform del 2026-11-16, hallazgo B4.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->decimal('quantity', 15, 4)->change();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->integer('quantity')->change();
        });
    }
};
