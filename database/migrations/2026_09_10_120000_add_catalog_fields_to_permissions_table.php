<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campos del catalogo de permisos (rediseno estilo Bind, 2026-09):
 * label y description legibles para la UI de roles, module para agrupar
 * por modulo de negocio y resource (prefijo sin verbo) para el toggle
 * maestro por grupo. Los valores los puebla PermissionCatalogSeeder
 * desde Modules/PermissionManager/config/permission_catalog.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->string('label')->nullable()->after('name');
            $table->string('description')->nullable()->after('label');
            $table->string('module')->nullable()->after('description')->index();
            $table->string('resource')->nullable()->after('module')->index();
        });
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn(['label', 'description', 'module', 'resource']);
        });
    }
};
