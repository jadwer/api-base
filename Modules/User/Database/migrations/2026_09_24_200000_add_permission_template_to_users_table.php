<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permisos por usuario (2026-09-24, decision A de Gabino): el rol funciona
 * como PLANTILLA que precarga permisos; el usuario queda con permisos
 * directos. Aqui solo se recuerda de que plantilla salio (informativo).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('permission_template', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('permission_template');
        });
    }
};
