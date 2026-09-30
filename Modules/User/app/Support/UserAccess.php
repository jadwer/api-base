<?php

namespace Modules\User\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\PermissionManager\Models\Permission;
use Modules\PermissionManager\Models\Role;
use Modules\User\Models\User;
use Spatie\Permission\PermissionRegistrar;

/**
 * Acceso por usuario (2026-09-24, decision A de Gabino).
 *
 * - Roles de SISTEMA se asignan como rol porque el codigo depende de ellos:
 *   god y admin (accesos totales, bypass en varios controladores) y customer
 *   (portal del cliente, filtros por contacto).
 * - Cualquier otro rol es PLANTILLA: solo precarga permisos en la UI. Al
 *   guardar, el usuario queda con permisos DIRECTOS (model_has_permissions)
 *   y sin roles no-sistema, asi que se pueden agregar y quitar permisos por
 *   usuario. Editar la plantilla despues no cambia usuarios ya creados.
 * - Usuarios existentes: describe() devuelve sus permisos EFECTIVOS (rol +
 *   directos) para que la pestana los muestre; al guardar se convierten.
 */
final class UserAccess
{
    public const SYSTEM_ROLES = ['god', 'admin', 'customer'];

    /**
     * Visibilidad de listados (indexQuery): si el usuario ve TODOS los
     * registros del recurso o solo los suyos. Antes se decidia por rol
     * (god/admin/tech) y un vendedor con permisos por usuario (plantillas)
     * veia listas vacias. Ahora: god/admin todo; customer solo lo suyo;
     * cualquier otro todo si tiene el permiso {recurso}.index (tech se
     * conserva mientras exista como rol). La restriccion por sucursal se
     * aplica aparte (Branch\Concerns\BranchScoped).
     */
    public static function seesAll($user, string $indexPermission): bool
    {
        if (! $user) {
            return false;
        }
        if ($user->hasAnyRole(['god', 'admin', 'administrator'])) {
            return true;
        }
        if ($user->hasRole('customer')) {
            return false;
        }

        return $user->hasRole('tech') || $user->can($indexPermission);
    }

    /** Estado de acceso para la pestana de permisos. */
    public static function describe(User $user): array
    {
        $user->loadMissing(['roles', 'permissions']);
        $effective = $user->getAllPermissions();

        return [
            'userId' => (string) $user->id,
            'systemRoles' => $user->roles->pluck('name')->intersect(self::SYSTEM_ROLES)->values()->all(),
            'templateRoles' => $user->roles->pluck('name')->diff(self::SYSTEM_ROLES)->values()->all(),
            'permissionTemplate' => $user->permission_template,
            'effectivePermissionIds' => $effective->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all(),
            'directPermissionIds' => $user->permissions->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all(),
            'systemRoleOptions' => self::SYSTEM_ROLES,
        ];
    }

    /**
     * Aplica el acceso: roles de sistema, permisos directos y plantilla de
     * origen. Quita cualquier rol no-sistema (quedaron convertidos a
     * directos). Todo en una transaccion.
     *
     * @param  string[]  $systemRoles
     * @param  int[]  $permissionIds
     */
    public static function apply(User $user, array $systemRoles, ?string $template, array $permissionIds, User $actor): User
    {
        $systemRoles = array_values(array_unique($systemRoles));
        $permissionIds = array_values(array_unique(array_map('intval', $permissionIds)));

        $invalidRoles = array_diff($systemRoles, self::SYSTEM_ROLES);
        if ($invalidRoles !== []) {
            throw ValidationException::withMessages([
                'systemRoles' => 'Roles de sistema no validos: ' . implode(', ', $invalidRoles) . '. Solo se permiten: ' . implode(', ', self::SYSTEM_ROLES) . '.',
            ]);
        }

        if ($template !== null && $template !== '' && ! Role::where('name', $template)->where('guard_name', 'api')->exists()) {
            throw ValidationException::withMessages(['template' => "La plantilla '{$template}' no existe."]);
        }

        $known = Permission::where('guard_name', 'api')->whereIn('id', $permissionIds)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $unknown = array_diff($permissionIds, $known);
        if ($unknown !== []) {
            throw ValidationException::withMessages(['permissionIds' => 'Permisos inexistentes: ' . implode(', ', $unknown) . '.']);
        }

        // Guardas: solo god otorga o retira god; nadie se deja a si mismo sin
        // poder administrar usuarios (evita quedarse fuera del sistema).
        $hadGod = $user->hasRole('god');
        $wantsGod = in_array('god', $systemRoles, true);
        if ($hadGod !== $wantsGod && ! $actor->hasRole('god')) {
            throw ValidationException::withMessages(['systemRoles' => 'Solo un usuario god puede otorgar o retirar el rol god.']);
        }
        if ($actor->is($user)) {
            $keepsAccess = $wantsGod || in_array('admin', $systemRoles, true)
                || Permission::where('name', 'users.update')->whereIn('id', $known)->exists();
            if (! $keepsAccess) {
                throw ValidationException::withMessages(['permissionIds' => 'No puedes quitarte a ti mismo el permiso de editar usuarios.']);
            }
        }

        DB::transaction(function () use ($user, $systemRoles, $template, $known) {
            $user->syncRoles($systemRoles);
            $user->syncPermissions($known);
            $user->forceFill(['permission_template' => $template ?: null])->save();
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        activity('user_access')
            ->performedOn($user)
            ->causedBy($actor)
            ->withProperties([
                'system_roles' => $systemRoles,
                'template' => $template,
                'permission_count' => count($known),
            ])
            ->log('Acceso del usuario actualizado');

        return $user->fresh(['roles', 'permissions']);
    }
}
