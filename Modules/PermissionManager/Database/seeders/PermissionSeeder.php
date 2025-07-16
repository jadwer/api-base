<?php

namespace Modules\PermissionManager\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\PermissionManager\Models\Permission;

class PermissionSeeder extends Seeder
{
    
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Crear usuario System (causer_id = 1) si no existe
        $system = User::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'System',
                'email' => 'system@audit.local',
                'password' => 'system',
                'status' => 'active',
            ]
        );

        $permissions = [
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
            'permissions.create',
            'permissions.store',
            'permissions.update',
            'permissions.delete',
            'permissions.assign',
            'permissions.revoke',
            // Users
            'users.view',
            'users.index',
            'users.create',
            'users.store',
            'users.update',
            'users.delete',
            // Own profile
            'profile.view',
            'profile.update',
        ];

        foreach ($permissions as $perm) {
            $created = Permission::firstOrCreate([
                'name' => $perm,
                'guard_name' => 'api',
            ]);

            activity()
                ->causedBy($system)
                ->event('seeding')
                ->withProperties([
                    'attributes' => $created->only('id', 'name'),
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'SeederScript'
                ])
                ->log("Permiso '{$perm}' creado o actualizado");

        }
    }
}
