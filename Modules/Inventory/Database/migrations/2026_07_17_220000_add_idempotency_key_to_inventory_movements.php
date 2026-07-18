<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * R1 (diseno post-refactor): clave de idempotencia con UNIQUE real en BD.
     *
     * Los guards previos (SELECT exists / whereJsonContains sobre metadata JSON)
     * tenian ventana de carrera check-then-insert y comportamiento distinto por
     * driver (MySQL vs SQLite en tests). Con la columna + unique, la unicidad la
     * garantiza la base de datos, no una consulta previa.
     *
     * Nullable a proposito: los movimientos SIN clave natural (tandas de recepcion
     * de compra, movimientos manuales) no la usan, y multiples NULL no violan el
     * unique ni en MySQL ni en SQLite.
     *
     * Formato de clave: "{flujo}:{id}:item:{itemId}" (ej. "remission:12:item:34",
     * "sales_cancel:exit:56"). Determinista por operacion de negocio.
     */
    public function up(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->string('idempotency_key', 120)->nullable()->after('reference_id');
            $table->unique('idempotency_key', 'inventory_movements_idempotency_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropUnique('inventory_movements_idempotency_key_unique');
            $table->dropColumn('idempotency_key');
        });
    }
};
