<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permiso `sales-orders.confirm` (flujo guiado F2, D4): confirmar un pedido
 * pendiente dejaba de depender del rol god/admin y pasa a ser asignable por
 * usuario desde Usuarios. Se crea y se adjunta a god y admin, como hoy.
 * Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $id = DB::table('permissions')->where('name', 'sales-orders.confirm')->where('guard_name', 'api')->value('id');
        if (!$id) {
            $id = DB::table('permissions')->insertGetId([
                'name' => 'sales-orders.confirm',
                'guard_name' => 'api',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach (DB::table('roles')->whereIn('name', ['god', 'admin'])->where('guard_name', 'api')->pluck('id') as $roleId) {
            DB::table('role_has_permissions')->updateOrInsert(['permission_id' => $id, 'role_id' => $roleId], []);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $id = DB::table('permissions')->where('name', 'sales-orders.confirm')->where('guard_name', 'api')->value('id');
        if ($id) {
            DB::table('role_has_permissions')->where('permission_id', $id)->delete();
            DB::table('model_has_permissions')->where('permission_id', $id)->delete();
            DB::table('permissions')->where('id', $id)->delete();
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
