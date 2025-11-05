<?php

namespace Modules\CRM\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class CRMAssignPermissionsSeeder extends Seeder
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
        $customerRole = Role::where('name', 'customer')->where('guard_name', 'api')->first();

        // God gets all CRM permissions
        if ($godRole) {
            $allPermissions = Permission::where('guard_name', 'api')
                ->where('name', 'like', 'crm.%')
                ->get();
            $godRole->givePermissionTo($allPermissions);
        }

        // Admin gets all CRM permissions
        if ($adminRole) {
            $adminPermissions = Permission::where('guard_name', 'api')
                ->where('name', 'like', 'crm.%')
                ->get();
            $adminRole->givePermissionTo($adminPermissions);
        }

        // Tech gets read-only CRM permissions (index, show)
        if ($techRole) {
            $techPermissions = Permission::where('guard_name', 'api')
                ->where('name', 'like', 'crm.%')
                ->where(function ($query) {
                    $query->where('name', 'like', '%.index')
                          ->orWhere('name', 'like', '%.show');
                })
                ->get();
            $techRole->givePermissionTo($techPermissions);
        }

        // Customer gets NO CRM permissions (internal sales tool)
        // CRM is for sales teams only, customers don't access this module
    }
}
