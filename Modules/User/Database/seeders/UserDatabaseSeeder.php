<?php

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\User\Models\User;


class UserDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ejecutar roles y permisos
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            AssignPermissionsSeeder::class,

        ]);

        // Usuario God fijo
        $god = User::factory()->create([
            'name' => 'God Admin',
            'email' => 'god@example.com',
            'password' => bcrypt('supersecure'),
            'status' => 'active',
        ]);
        $god->assignRole('god');

        // 2 usuarios aleatorios con rol 'customer'
        User::factory(2)->create()->each(function ($user) {
            $user->assignRole('customer');
        });
    }
}
