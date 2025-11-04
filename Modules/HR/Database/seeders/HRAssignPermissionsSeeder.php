<?php

namespace Modules\HR\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class HRAssignPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get roles
        $godRole = Role::where('name', 'god')->where('guard_name', 'api')->first();
        $adminRole = Role::where('name', 'admin')->where('guard_name', 'api')->first();
        $techRole = Role::where('name', 'tech')->where('guard_name', 'api')->first();

        // God gets all HR permissions
        if ($godRole) {
            $allPermissions = Permission::where('guard_name', 'api')
                ->where('name', 'like', 'hr.%')
                ->get();
            $godRole->givePermissionTo($allPermissions);
        }

        // Admin gets all HR permissions
        if ($adminRole) {
            $adminPermissions = Permission::where('guard_name', 'api')
                ->where('name', 'like', 'hr.%')
                ->get();
            $adminRole->givePermissionTo($adminPermissions);
        }

        // Tech gets read-only HR permissions
        if ($techRole) {
            $techPermissions = Permission::where('guard_name', 'api')
                ->where('name', 'like', 'hr.%')
                ->where(function ($query) {
                    $query->where('name', 'like', '%.index')
                          ->orWhere('name', 'like', '%.show');
                })
                ->get();
            $techRole->givePermissionTo($techPermissions);
        }

        // Customer role has no HR access by design
    }
}
