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
    /**
     * Roles del portal del cliente. En produccion el rol se llama `cliente`
     * (asi lo creo el cliente desde Usuarios); el seeder limpio lo llama
     * `customer`. Hasta 2026-11 solo se reconocia `customer`, asi que un
     * usuario con rol `cliente` y permiso quotes.index veia TODAS las
     * cotizaciones (seesAll devolvia true). Cualquiera de los dos restringe.
     */
    public const CUSTOMER_ROLES = ['customer', 'cliente'];

    public const SYSTEM_ROLES = ['god', 'admin', 'customer', 'cliente'];

    /** Si el usuario es un cliente del portal (solo ve lo de su contacto). */
    public static function isCustomer($user): bool
    {
        return $user ? $user->hasAnyRole(self::CUSTOMER_ROLES) : false;
    }

    /**
     * Visibilidad de listados (indexQuery): si el usuario ve TODOS los
     * registros del recurso o solo los suyos. Antes se decidia por rol
     * (god/admin/tech) y un vendedor con permisos por usuario (plantillas)
     * veia listas vacias. Ahora: god/admin todo; customer solo lo suyo;
     * cualquier otro todo si tiene el permiso {recurso}.index (tech se
     * conserva mientras exista como rol). La restriccion por sucursal se
     * aplica aparte (Branch\Concerns\BranchScoped).
     */
    /**
     * Si el usuario puede ver UN documento de un cliente: ve todo (personal) o
     * es el cliente dueno (su correo es el del contacto). Es la misma regla
     * que `indexQuery` de los Schemas; el `show` la repite para que un cliente
     * del portal no lea documentos ajenos cambiando el id (regla 8, 2026-11).
     */
    public static function seesDocument($user, string $indexPermission, ?int $contactId): bool
    {
        if (self::seesAll($user, $indexPermission)) {
            return true;
        }
        if (! $user || $contactId === null) {
            return false;
        }

        return \Modules\Contacts\Models\Contact::where('email', $user->email)->where('id', $contactId)->exists();
    }

    public static function seesAll($user, string $indexPermission): bool
    {
        if (! $user) {
            return false;
        }
        if ($user->hasAnyRole(['god', 'admin', 'administrator'])) {
            return true;
        }
        if (self::isCustomer($user)) {
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
