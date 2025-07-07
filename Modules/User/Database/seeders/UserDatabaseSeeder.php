<?php

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\User\Models\User;

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
                'password' => 'system',
                'status' => 'active',
            ]
        );

        activity()->causedBy($system)->log('Se ejecutó UserDatabaseSeeder');

    $this->call([
        PermissionSeeder::class,
    ]);

    $this->call([
        RoleSeeder::class,
    ]);

    $this->call([
        AssignPermissionsSeeder::class,
    ]);

    $this->call([
        UserSeeder::class,
    ]);
    }
}
