<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vendedor de la cotizacion (rediseno 12-11-2026): mismo campo que el pedido
 * (`sales_orders.assigned_to`), que ya usan las comisiones. Al convertir, el
 * pedido hereda el vendedor de la cotizacion.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->foreignId('assigned_to')->nullable()->after('contact_id')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_to');
        });
    }
};
