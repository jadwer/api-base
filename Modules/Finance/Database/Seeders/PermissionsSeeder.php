<?php

namespace Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding Finance permissions...');

        // Define all Finance module entities
        $entities = [
            'ar-invoices',
            'ap-invoices',
            'payments',
            'payment-applications',
            'bank-accounts',
            'payment-methods',
        ];

        // Create permissions for each entity
        $actions = ['index', 'show', 'store', 'update', 'destroy'];

        foreach ($entities as $entity) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(
                    ['name' => "{$entity}.{$action}"],
                    ['guard_name' => 'api']
                );
            }
        }

        // Assign permissions to God role (all permissions)
        $god = Role::where('name', 'god')->first();
        if ($god) {
            $this->command->warn("Assigning Finance permissions to {$god->name} role...");
            $permissions = Permission::where(function($query) use ($entities) {
                foreach ($entities as $entity) {
                    $query->orWhere('name', 'like', "{$entity}.%");
                }
            })->get();
            $god->givePermissionTo($permissions);
            $this->command->info("God role assigned all Finance permissions");
        }

        // Assign permissions to Admin role (all permissions)
        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $this->command->warn("Assigning Finance permissions to {$admin->name} role...");
            foreach ($entities as $entity) {
                $admin->givePermissionTo([
                    "{$entity}.index",
                    "{$entity}.show",
                    "{$entity}.store",
                    "{$entity}.update",
                    "{$entity}.destroy",
                ]);
            }
            $this->command->info("Admin role assigned all Finance permissions");
        }

        // Assign read-only permissions to Tech role
        $tech = Role::where('name', 'tech')->first();
        if ($tech) {
            $this->command->warn("Assigning read-only Finance permissions to {$tech->name} role...");
            foreach ($entities as $entity) {
                $tech->givePermissionTo([
                    "{$entity}.index",
                    "{$entity}.show",
                ]);
            }
            $this->command->info("Tech role assigned read-only Finance permissions");
        }

        // Customer role has no finance access by design
        $customer = Role::where('name', 'customer')->first();
        if ($customer) {
            $this->command->info("Customer role has no Finance permissions (as expected)");
        }

        $this->command->info('Finance permissions seeded successfully!');
    }
}
