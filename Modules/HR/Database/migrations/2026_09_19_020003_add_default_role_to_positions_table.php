<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Se liga la actividad con los permisos" (Jasim 18-sep-2026): un puesto
 * puede traer un rol Spatie por defecto; al ligar un empleado con ese
 * puesto a un usuario sin roles, el usuario recibe ese rol. Los permisos
 * individuales se siguen ajustando en el editor de usuarios.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->string('default_role', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->dropColumn('default_role');
        });
    }
};
