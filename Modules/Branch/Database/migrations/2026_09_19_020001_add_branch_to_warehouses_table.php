<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cada almacen pertenece a una sucursal. El inventario sigue siendo
 * compartido (decision Jasim 18-sep-2026): el stock no se oculta, solo se
 * sabe de que sucursal es via su almacen. Backfill: almacenes a la Matriz.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->onDelete('restrict');
        });

        $mainId = DB::table('branches')->where('is_main', true)->value('id');
        if ($mainId) {
            DB::table('warehouses')->whereNull('branch_id')->update(['branch_id' => $mainId]);
        }
    }

    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
        });
    }
};
