<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * El rol del portal (customer; antes tambien cliente) tenia acciones de CFDI
 * que son del personal: generar PDF y XML, prefactura, validar ante el SAT y
 * consultar el estatus de cancelacion. El portal solo necesita listar, ver,
 * previsualizar y descargar SUS facturas (el seeder ya lo dice asi; estas
 * filas venian de un seeder anterior). Se revocan del rol y de cualquier
 * usuario del portal que las tuviera directas (regla 8, 2026-11-09).
 */
return new class extends Migration
{
    private const REVOKE = [
        'billing.cfdi-invoices.generate-pdf',
        'billing.cfdi-invoices.generate-xml',
        'billing.cfdi-invoices.prefactura',
        'billing.cfdi-invoices.validate',
        'billing.cfdi-invoices.cancellation-status',
    ];

    public function up(): void
    {
        $permissionIds = DB::table('permissions')->whereIn('name', self::REVOKE)->where('guard_name', 'api')->pluck('id');
        $roleIds = DB::table('roles')->whereIn('name', ['customer', 'cliente'])->where('guard_name', 'api')->pluck('id');
        if ($permissionIds->isEmpty() || $roleIds->isEmpty()) {
            return;
        }
        DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->whereIn('role_id', $roleIds)->delete();

        $portalUsers = DB::table('model_has_roles')->whereIn('role_id', $roleIds)->pluck('model_id');
        DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->whereIn('model_id', $portalUsers)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // No se devuelven: eran un exceso, no una funcion del portal.
    }
};
