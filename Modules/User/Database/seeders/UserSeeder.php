<?php

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\User\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $system = User::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'System',
                'email' => 'system@audit.local',
                'password' => 'system',
                'status' => 'active',
            ]
        );
        $system->assignRole('god');

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

        $customer = User::factory()->create([
            'name' => 'Customer Test',
            'email' => 'customer@example.com',
            'status' => 'active',
        ]);
        $customer->assignRole('customer');
    }
}
