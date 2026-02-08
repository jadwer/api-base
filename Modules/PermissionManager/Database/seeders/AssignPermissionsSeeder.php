<?php

namespace Modules\PermissionManager\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\PermissionManager\Models\Role;
use Modules\PermissionManager\Models\Permission;
use Modules\User\Models\User;

class AssignPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuario System (causer_id = 1) si no existe
        $system = User::firstOrCreate(
            ['email' => 'system@audit.local'],
            [
                'name' => 'System',
                'password' => 'system',
                'status' => 'active',
            ]
        );

        $permissionsMap = [
            'god' => Permission::all()->pluck('name')->toArray(),
            'admin' => [
                'users.index', 'users.show', 'users.store', 'users.update', 'users.destroy',
                'roles.show', 'roles.index',
                'permissions.show', 'permissions.index', 'permissions.assign',
                'profile.show', 'profile.update',
            ],
            'tech' => [
                'users.index', 'users.show', 'users.destroy',
                'profile.show', 'profile.update',
            ],
            'customer' => [
                'profile.show', 'profile.update',
            ],
            'guest' => [],
        ];
    
        foreach ($permissionsMap as $roleName => $permissionNames) {
            $role = Role::where('name', $roleName)->where('guard_name', 'api')->first();

            if (! $role) {
                $this->command->error("Role '{$roleName}' not found.");
                continue;
            }

            $permissions = Permission::whereIn('name', $permissionNames)->get();
            $role->givePermissionTo($permissions);

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
