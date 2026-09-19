<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Siembra la sucursal principal "Matriz" como dato del sistema (no como
 * seeder): las migraciones que agregan branch_id a usuarios, almacenes y
 * documentos hacen backfill hacia ella, y prod solo corre migraciones.
 * Idempotente: si ya hay una sucursal principal no hace nada.
 */
return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('branches')->where('is_main', true)->exists();
        if ($exists) {
            return;
        }

        DB::table('branches')->insert([
            'name' => 'Matriz',
            'code' => 'MATRIZ',
            'address' => null,
            'city' => null,
            'state' => null,
            'postal_code' => null,
            'phone' => null,
            'email' => null,
            'is_active' => true,
            'is_main' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // No se borra: puede tener usuarios, almacenes y documentos colgando.
    }
};
