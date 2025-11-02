<?php

namespace App\Database\Seeders\Concerns;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Trait for bulk creating permissions efficiently
 *
 * This trait replaces individual Permission::firstOrCreate() calls
 * with bulk inserts, dramatically improving seeding performance.
 *
 * Usage:
 *   use BulkPermissions;
 *
 *   $this->bulkCreatePermissions([
 *       'module.entity.index',
 *       'module.entity.show',
 *       'module.entity.store',
 *   ]);
 */
trait BulkPermissions
{
    /**
     * Bulk create permissions using insertOrIgnore
     *
     * @param array $permissions Array of permission names
     * @param string $guardName Guard name (default: 'api')
     * @return void
     */
    protected function bulkCreatePermissions(array $permissions, string $guardName = 'api'): void
    {
        $now = now();

        $data = collect($permissions)->map(function($name) use ($guardName, $now) {
            return [
                'name' => $name,
                'guard_name' => $guardName,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->toArray();

        // Use insertOrIgnore to avoid duplicates without extra queries
        DB::table('permissions')->insertOrIgnore($data);

        // Clear Spatie permission cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Bulk create permissions and assign to roles
     *
     * @param array $permissions Array of permission names
     * @param array $roleNames Array of role names to assign permissions to
     * @param string $guardName Guard name (default: 'api')
     * @return void
     */
    protected function bulkCreateAndAssignPermissions(
        array $permissions,
        array $roleNames,
        string $guardName = 'api'
    ): void {
        // Create permissions in bulk
        $this->bulkCreatePermissions($permissions, $guardName);

        // Assign to roles efficiently
        foreach ($roleNames as $roleName) {
            $role = \Spatie\Permission\Models\Role::findByName($roleName, $guardName);

            // Get permission IDs in one query
            $permissionIds = DB::table('permissions')
                ->whereIn('name', $permissions)
                ->where('guard_name', $guardName)
                ->pluck('id')
                ->toArray();

            // Bulk insert role_has_permissions
            $rolePermissions = collect($permissionIds)->map(function($permissionId) use ($role) {
                return [
                    'permission_id' => $permissionId,
                    'role_id' => $role->id,
                ];
            })->toArray();

            DB::table('role_has_permissions')->insertOrIgnore($rolePermissions);
        }

        // Clear cache once at the end
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
