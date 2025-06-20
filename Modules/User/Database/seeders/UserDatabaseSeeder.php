<?php

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\User\Models\User;

class UserDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Ejecutar seeders relacionados
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            AssignPermissionsSeeder::class,
        ]);

        // ✅ Usuario God (tiene todos los permisos)
        $god = User::factory()->create([
            'name' => 'God Admin',
            'email' => 'god@example.com',
            'password' => bcrypt('supersecure'),
            'status' => 'active',
        ]);
        $god->assignRole('god');

        // ✅ Usuario Admin (con permisos limitados)
        $admin = User::factory()->create([
            'name' => 'Administrador General',
            'email' => 'admin@example.com',
            'password' => bcrypt('secureadmin'),
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        // ✅ Usuario Tech (sin permisos elevados)
        $tech = User::factory()->create([
            'name' => 'Técnico',
            'email' => 'tech@example.com',
            'password' => bcrypt('securetech'),
            'status' => 'active',
        ]);
        $tech->assignRole('tech'); 
        
        // ✅ Usuarios comunes para pruebas de index/show/delete
        User::factory()->create([
            'name' => 'Cliente Uno',
            'email' => 'cliente1@example.com',
            'status' => 'active',
        ])->assignRole('customer');

        User::factory()->create([
            'name' => 'Cliente Dos',
            'email' => 'cliente2@example.com',
            'status' => 'active',
        ])->assignRole('customer');
    }
}
