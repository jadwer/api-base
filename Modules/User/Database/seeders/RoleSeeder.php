<?php

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Modules\User\Models\User;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'god', 'description' => 'Superadmin del sistema'],
            ['name' => 'admin', 'description' => 'Administrador general'],
            ['name' => 'tech', 'description' => 'Usuario técnico de soporte'],
            ['name' => 'customer', 'description' => 'Cliente registrado'],
            ['name' => 'guest', 'description' => 'Invitado sin login'],
        ];

        foreach ($roles as $role) {
            $created = Role::firstOrCreate(
                ['name' => $role['name'], 'guard_name' => 'api'],
                ['description' => $role['description'] ?? null]
            );

            activity()
                ->causedBy(User::find(1))
                ->event('seeding')
                ->withProperties([
                    'attributes' => $created->only('id', 'name', 'description'),
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'SeederScript'
                ])
                ->log("Rol {$created->name} creado o actualizado");
        }
    }
}
