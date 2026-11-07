<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Los roles de sistema son identificadores de codigo (`customer`, `guest`); lo
 * que ve la gente es `description`. En la produccion del primer cliente se crearon
 * en espanol (`cliente`, `invitado`) y el codigo solo reconocia `customer`
 * como rol del portal, asi que un `cliente` con quotes.index veia todas las
 * cotizaciones (2026-11-07). Aqui se renombran conservando usuarios y
 * permisos; si el destino ya existe, se fusionan. Idempotente.
 */
return new class extends Migration
{
    private const RENAMES = [
        'cliente' => ['customer', 'Cliente (portal del cliente)'],
        'invitado' => ['guest', 'Invitado'],
    ];

    public function up(): void
    {
        if (!Schema::hasTable('roles')) {
            return;
        }

        foreach (self::RENAMES as $from => [$to, $description]) {
            $source = DB::table('roles')->where('name', $from)->where('guard_name', 'api')->first();
            if (!$source) {
                continue;
            }
            $target = DB::table('roles')->where('name', $to)->where('guard_name', 'api')->first();

            if (!$target) {
                DB::table('roles')->where('id', $source->id)->update([
                    'name' => $to,
                    'description' => $source->description ?: $description,
                    'updated_at' => now(),
                ]);
                continue;
            }

            // Fusionar: usuarios y permisos del rol en espanol pasan al identificador.
            DB::table('model_has_roles')->where('role_id', $source->id)->get()->each(function ($row) use ($target) {
                DB::table('model_has_roles')->updateOrInsert(
                    ['role_id' => $target->id, 'model_type' => $row->model_type, 'model_id' => $row->model_id],
                    []
                );
            });
            DB::table('role_has_permissions')->where('role_id', $source->id)->get()->each(function ($row) use ($target) {
                DB::table('role_has_permissions')->updateOrInsert(
                    ['permission_id' => $row->permission_id, 'role_id' => $target->id],
                    []
                );
            });
            DB::table('model_has_roles')->where('role_id', $source->id)->delete();
            DB::table('role_has_permissions')->where('role_id', $source->id)->delete();
            DB::table('roles')->where('id', $source->id)->delete();
            if (!$target->description) {
                DB::table('roles')->where('id', $target->id)->update(['description' => $description]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // El nombre en espanol era un dato del cliente, no un estado del sistema:
        // no se restaura. Los usuarios conservan sus permisos bajo el identificador.
    }
};
