<?php

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Modules\User\Models\User;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Users
            'users.view', 'users.index', 'users.create', 'users.store', 'users.update', 'users.delete',
            // Roles
            'roles.view', 'roles.index', 'roles.create', 'roles.store', 'roles.update', 'roles.delete',
            // Permissions
            'permissions.view', 'permissions.index', 'permissions.assign', 'permissions.revoke',
            // Own Profile
            'profile.view', 'profile.update',
        ];

        foreach ($permissions as $perm) {
            $created = Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'api']);

            activity()
                ->causedBy(User::find(1))
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
