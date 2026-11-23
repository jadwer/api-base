<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Los inicios y cierres de sesion se registraban con el texto en `description`
 * y `event` vacio, asi que el filtro por evento de la bitacora no los encontraba
 * (PLA-18). Se copia la descripcion al evento en los registros existentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('activity_log') || ! Schema::hasColumn('activity_log', 'event')) {
            return;
        }
        foreach (['login', 'logout', 'login_failed'] as $event) {
            DB::table('activity_log')->whereNull('event')->where('description', $event)->update(['event' => $event]);
        }
    }

    public function down(): void
    {
        // Sin reversa: el dato original sigue en `description`.
    }
};
