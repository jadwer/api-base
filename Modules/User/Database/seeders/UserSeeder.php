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
            ['email' => 'system@audit.local'],
            [
                'name' => 'System',
                'password' => 'system',
                'status' => 'active',
            ]
        );
        if (!$system->hasRole('god')) {
            $system->assignRole('god');
        }

        $god = User::firstOrCreate(
            ['email' => 'god@example.com'],
            [
                'name' => 'God Admin',
                'password' => 'supersecure',
                'status' => 'active',
            ]
        );
        if (!$god->hasRole('god')) {
            $god->assignRole('god');
            activity()->causedBy($system)->performedOn($god)->log('Creado usuario God');
        }

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrador General',
                'password' => 'secureadmin',
                'status' => 'active',
            ]
        );
        if (!$admin->hasRole('admin')) {
            $admin->assignRole('admin');
            activity()->causedBy($system)->performedOn($admin)->log('Creado usuario Admin');
        }

        $tech = User::firstOrCreate(
            ['email' => 'tech@example.com'],
            [
                'name' => 'Técnico',
                'password' => 'securetech',
                'status' => 'active',
            ]
        );
        if (!$tech->hasRole('tech')) {
            $tech->assignRole('tech');
            activity()->causedBy($system)->performedOn($tech)->log('Creado usuario Tech');
        }

        $cliente1 = User::firstOrCreate(
            ['email' => 'cliente1@example.com'],
            [
                'name' => 'Cliente Uno',
                'password' => 'customer',
                'status' => 'active',
            ]
        );
        if (!$cliente1->hasRole('customer')) {
            $cliente1->assignRole('customer');
        }

        $cliente2 = User::firstOrCreate(
            ['email' => 'cliente2@example.com'],
            [
                'name' => 'Cliente Dos',
                'password' => 'customer',
                'status' => 'active',
            ]
        );
        if (!$cliente2->hasRole('customer')) {
            $cliente2->assignRole('customer');
        }

        $customer = User::firstOrCreate(
            ['email' => 'customer@example.com'],
            [
                'name' => 'Customer Test',
                'password' => 'customer',
                'status' => 'active',
            ]
        );
        if (!$customer->hasRole('customer')) {
            $customer->assignRole('customer');
        }
    }
}
