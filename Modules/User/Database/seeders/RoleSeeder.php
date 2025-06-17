<?php

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['name' => 'god', 'description' => 'Superadmin del sistema'],
            ['name' => 'admin', 'description' => 'Administrador general'],
            ['name' => 'customer', 'description' => 'Cliente registrado'],
            ['name' => 'guest', 'description' => 'Invitado sin login'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['name' => $role['name'], 'guard_name' => 'api'],
                ['description' => $role['description'] ?? null]
            );
        }
    }
}
