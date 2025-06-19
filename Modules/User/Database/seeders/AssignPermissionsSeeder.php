<?php

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AssignPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'god' => Permission::all(), // todos los permisos

            'admin' => [
                'users.view',
                'users.index',
                'users.create',
                'users.update',

                'roles.view',
                'roles.index',

                'permissions.view',
                'permissions.index',
                'permissions.assign',

                'profile.view',
                'profile.update',
            ],

            'customer' => [
                'profile.view',
                'profile.update',
            ],

            'guest' => [
                // No permissions (solo acceso público, como ver productos si se agregan)
            ],
        ];

        foreach ($roles as $roleName => $perms) {
            $role = Role::where('name', $roleName)
                ->where('guard_name', 'api')
                ->first();

            if (!$role) {
                $this->command->error("Role '{$roleName}' not found.");
                continue;
            }

            $role->syncPermissions($perms);
        }
    }
}
