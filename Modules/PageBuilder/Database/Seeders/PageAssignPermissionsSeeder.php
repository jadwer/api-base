<?php

namespace Modules\PageBuilder\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PageAssignPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $god = Role::where('name', 'god')->first();

        if ($god) {
            $permissions = Permission::where('name', 'like', 'page.%')->get();
            $god->syncPermissions($permissions);
        }

        $admin = Role::where('name', 'admin')->first();

        if ($admin) {
            $admin->givePermissionTo([
                'page.index',
                'page.show',
                'page.store',
                'page.update',
                'page.destroy',
            ]);
        }
    }
}
