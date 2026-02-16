<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Normalize permission names to standard convention:
 *   .view   -> .show
 *   .create -> .store
 *   .delete -> .destroy
 *
 * Also creates missing permissions referenced by authorizers.
 */
return new class extends Migration
{
    /**
     * Old -> New permission name mappings.
     * If new already exists, roles from old are transferred, then old is deleted.
     * If new does not exist, old is simply renamed.
     */
    private array $renames = [
        'users.delete' => 'users.destroy',
        'users.view' => 'users.show',
        'users.create' => 'users.store',
        'roles.delete' => 'roles.destroy',
        'roles.view' => 'roles.show',
        'roles.create' => 'roles.store',
        'permissions.delete' => 'permissions.destroy',
        'permissions.view' => 'permissions.show',
        'permissions.create' => 'permissions.store',
        'stock.delete' => 'stock.destroy',
        'stock.view' => 'stock.show',
        'stock.create' => 'stock.store',
        'warehouses.delete' => 'warehouses.destroy',
        'warehouses.view' => 'warehouses.show',
        'warehouses.create' => 'warehouses.store',
        'warehouse-locations.delete' => 'warehouse-locations.destroy',
        'warehouse-locations.view' => 'warehouse-locations.show',
        'warehouse-locations.create' => 'warehouse-locations.store',
        'product-batches.delete' => 'product-batches.destroy',
        'product-batches.view' => 'product-batches.show',
        'product-batches.create' => 'product-batches.store',
        'quotes.view' => 'quotes.show',
        'quote-items.view' => 'quote-items.show',
        'sales-orders.view' => 'sales-orders.show',
        'sales-order-items.view' => 'sales-order-items.show',
        'shipments.view' => 'shipments.show',
        'shipment-items.view' => 'shipment-items.show',
        'remissions.view' => 'remissions.show',
        'remission-items.view' => 'remission-items.show',
        'backorders.view' => 'backorders.show',
        'customers.view' => 'customers.show',
        'discount-rules.view' => 'discount-rules.show',
        'profile.view' => 'profile.show',
    ];

    /**
     * Permissions that authorizers reference but don't exist in DB at all.
     */
    private array $missing = [
        'budget-allocations.store',
        'budget-allocations.update',
        'budget-allocations.destroy',
    ];

    public function up(): void
    {
        $guardName = 'api';
        $now = now();

        // Step 1: Process renames
        foreach ($this->renames as $oldName => $newName) {
            $oldPerm = DB::table('permissions')
                ->where('name', $oldName)
                ->where('guard_name', $guardName)
                ->first();

            if (!$oldPerm) {
                continue; // Old permission doesn't exist, skip
            }

            $newPerm = DB::table('permissions')
                ->where('name', $newName)
                ->where('guard_name', $guardName)
                ->first();

            if ($newPerm) {
                // New permission already exists - transfer role assignments from old to new
                $oldRoleIds = DB::table('role_has_permissions')
                    ->where('permission_id', $oldPerm->id)
                    ->pluck('role_id')
                    ->toArray();

                foreach ($oldRoleIds as $roleId) {
                    DB::table('role_has_permissions')->insertOrIgnore([
                        'permission_id' => $newPerm->id,
                        'role_id' => $roleId,
                    ]);
                }

                // Delete old permission's role assignments and the permission itself
                DB::table('role_has_permissions')
                    ->where('permission_id', $oldPerm->id)
                    ->delete();
                DB::table('model_has_permissions')
                    ->where('permission_id', $oldPerm->id)
                    ->delete();
                DB::table('permissions')
                    ->where('id', $oldPerm->id)
                    ->delete();
            } else {
                // New permission doesn't exist - just rename
                DB::table('permissions')
                    ->where('id', $oldPerm->id)
                    ->update(['name' => $newName, 'updated_at' => $now]);
            }
        }

        // Step 2: Create missing permissions and assign to god/admin roles
        $rolesToAssign = DB::table('roles')
            ->whereIn('name', ['god', 'admin'])
            ->where('guard_name', $guardName)
            ->pluck('id')
            ->toArray();

        foreach ($this->missing as $permName) {
            $exists = DB::table('permissions')
                ->where('name', $permName)
                ->where('guard_name', $guardName)
                ->exists();

            if (!$exists) {
                $permId = DB::table('permissions')->insertGetId([
                    'name' => $permName,
                    'guard_name' => $guardName,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                foreach ($rolesToAssign as $roleId) {
                    DB::table('role_has_permissions')->insertOrIgnore([
                        'permission_id' => $permId,
                        'role_id' => $roleId,
                    ]);
                }
            }
        }

        // Step 3: Clear Spatie permission cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Reverse: rename new back to old
        $guardName = 'api';
        $now = now();

        $reverseRenames = array_flip($this->renames);

        foreach ($reverseRenames as $currentName => $originalName) {
            // Only rename back if the original doesn't already exist
            $originalExists = DB::table('permissions')
                ->where('name', $originalName)
                ->where('guard_name', $guardName)
                ->exists();

            if (!$originalExists) {
                DB::table('permissions')
                    ->where('name', $currentName)
                    ->where('guard_name', $guardName)
                    ->update(['name' => $originalName, 'updated_at' => $now]);
            }
        }

        // Remove missing permissions that were created
        foreach ($this->missing as $permName) {
            $perm = DB::table('permissions')
                ->where('name', $permName)
                ->where('guard_name', $guardName)
                ->first();

            if ($perm) {
                DB::table('role_has_permissions')
                    ->where('permission_id', $perm->id)
                    ->delete();
                DB::table('model_has_permissions')
                    ->where('permission_id', $perm->id)
                    ->delete();
                DB::table('permissions')
                    ->where('id', $perm->id)
                    ->delete();
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
