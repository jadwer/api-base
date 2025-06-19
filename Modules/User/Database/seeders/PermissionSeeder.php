<?php

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
           // Users
            'users.view',
            'users.index',
            'users.create',
            'users.store',
            'users.update',
            'users.delete',

            // Roles
            'roles.view',
            'roles.index',
            'roles.create',
            'roles.store',
            'roles.update',
            'roles.delete',

            // Permissions
            'permissions.view',
            'permissions.index',
            'permissions.assign',
            'permissions.revoke',

            // Own Profile
            'profile.view',
            'profile.update',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }
    }
}
