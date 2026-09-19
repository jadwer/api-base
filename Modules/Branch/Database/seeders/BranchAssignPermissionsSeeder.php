<?php

namespace Modules\Branch\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class BranchAssignPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // God: ALL permissions
        $god = Role::where('name', 'god')->first();
        if ($god) {
            $permissions = Permission::where(function ($query) {
            $query->where('name', 'like', 'branches.%');
            })->get();
            $god->givePermissionTo($permissions);
        }

        // Admin: ALL permissions
        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo([
                // Branch
                'branches.index',
                'branches.show',
                'branches.store',
                'branches.update',
                'branches.destroy',
            ]);
        }

        // Tech: read-only (index + show)
        $tech = Role::where('name', 'tech')->first();
        if ($tech) {
            $tech->givePermissionTo([
                'branches.index',
                'branches.show',
            ]);
        }

        // Customer: read-only
        $customer = Role::where('name', 'customer')->first();
        if ($customer) {
            $customer->givePermissionTo([
                'branches.index',
                'branches.show',
            ]);
        }
    }
}
