<?php

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\User\Models\User;
use Modules\User\Database\Seeders\RoleSeeder;
use Modules\User\Database\Seeders\PermissionSeeder;
use Modules\User\Database\Seeders\AssignPermissionsSeeder;

class UserDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Crear usuario System (causer_id = 1) si no existe
        $system = User::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'System',
                'email' => 'system@audit.local',
                'password' => bcrypt('system'),
                'status' => 'active',
            ]
        );

        activity()->causedBy($system)->log('Se ejecutó UserDatabaseSeeder');

        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            AssignPermissionsSeeder::class,
        ]);

        $god = User::factory()->create([
            'name' => 'God Admin',
            'email' => 'god@example.com',
            'password' => 'supersecure',
            'status' => 'active',
        ]);
        $god->assignRole('god');
        activity()->causedBy($system)->performedOn($god)->log('Creado usuario God');

        $admin = User::factory()->create([
            'name' => 'Administrador General',
            'email' => 'admin@example.com',
            'password' => 'secureadmin',
            'status' => 'active',
        ]);
        $admin->assignRole('admin');
        activity()->causedBy($system)->performedOn($admin)->log('Creado usuario Admin');

        $tech = User::factory()->create([
            'name' => 'Técnico',
            'email' => 'tech@example.com',
            'password' => 'securetech',
            'status' => 'active',
        ]);
        $tech->assignRole('tech');
        activity()->causedBy($system)->performedOn($tech)->log('Creado usuario Tech');

        $cliente1 = User::factory()->create([
            'name' => 'Cliente Uno',
            'email' => 'cliente1@example.com',
            'status' => 'active',
        ]);
        $cliente1->assignRole('customer');

        $cliente2 = User::factory()->create([
            'name' => 'Cliente Dos',
            'email' => 'cliente2@example.com',
            'status' => 'active',
        ]);
        $cliente2->assignRole('customer');
    }
}
