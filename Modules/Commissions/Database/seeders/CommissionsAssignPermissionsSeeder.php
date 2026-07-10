<?php

namespace Modules\Commissions\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * v1: commissions are god/admin only. Salespeople (tech role) will get a
 * scoped index (own rows via forced user_id filter) in a later iteration.
 */
class CommissionsAssignPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = Permission::where('name', 'like', 'commissions.%')->get();

        foreach (['god', 'admin'] as $roleName) {
            $role = Role::where('name', $roleName)->first();

            if ($role) {
                $role->givePermissionTo($permissions);
            }
        }
    }
}
