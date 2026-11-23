<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Confirmar pedido (VEN-10; decision de Gabino, 2026-11-22): ventas y almacen
 * pueden confirmar. Los roles los arma cada instalacion, asi que la regla va por
 * lo que el rol ya hace: quien crea o edita pedidos (ventas) o genera remisiones
 * (almacen) recibe `sales-orders.confirm`. Los roles de cliente no.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }
        $confirm = DB::table('permissions')->where('name', 'sales-orders.confirm')->where('guard_name', 'api')->value('id');
        if (! $confirm) {
            return;
        }
        $sources = DB::table('permissions')
            ->whereIn('name', ['sales-orders.store', 'sales-orders.update', 'remissions.store'])
            ->where('guard_name', 'api')
            ->pluck('id');
        $roles = DB::table('role_has_permissions')
            ->join('roles', 'roles.id', '=', 'role_has_permissions.role_id')
            ->whereIn('role_has_permissions.permission_id', $sources)
            ->whereNotIn('roles.name', ['customer', 'guest'])
            ->distinct()
            ->pluck('roles.id');
        foreach ($roles as $roleId) {
            DB::table('role_has_permissions')->updateOrInsert(['permission_id' => $confirm, 'role_id' => $roleId], []);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Sin reversa: no se distingue lo otorgado aqui de lo asignado a mano.
    }
};
