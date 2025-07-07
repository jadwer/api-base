<?php

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Modules\User\Models\User;

class AssignPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $system = User::find(1); // usuario que causa los logs

        $permissionsMap = [
            'god' => [
                'users.index',
                'users.view',
                'users.create',
                'users.update',
                'users.delete',
                'roles.view',
                'roles.index',
                'permissions.view',
                'permissions.index',
                'permissions.assign',
                'profile.view',
                'profile.update',
            ],
            'admin' => [
                'users.index',
                'users.view',
                'users.create',
                'users.update',
                'users.delete',
                'roles.view',
                'roles.index',
                'permissions.view',
                'permissions.index',
                'permissions.assign',
                'profile.view',
                'profile.update',
            ],
            'tech' => [
                'users.index',
                'users.view',
                'users.delete',
                'profile.view',
                'profile.update',
            ],
            'customer' => [
                'profile.view',
                'profile.update',
            ],
            'guest' => [],
        ];

        foreach ($permissionsMap as $roleName => $permissionNames) {
            $role = Role::where('name', $roleName)
                ->where('guard_name', 'api')
                ->first();

            if (! $role) {
                $this->command->error("Role '{$roleName}' not found.");
                continue;
            }

            $permissions = Permission::whereIn('name', $permissionNames)->get();

            $role->syncPermissions($permissions);

            $this->command->info("Asignando a rol: {$roleName}");
            $this->command->info("Permisos: " . implode(', ', $permissions->pluck('name')->toArray()));

            activity()
                ->causedBy($system)
                ->event('permission-assignment')
                ->withProperties([
                    'attributes' => [
                        'role' => $role->name,
                        'permissions' => $permissions->pluck('name'),
                    ],
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'SeederScript',
                ])
                ->log("Permisos asignados al rol {$role->name}");
        }
    }
}
