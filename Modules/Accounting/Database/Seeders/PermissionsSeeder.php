<?php

namespace Modules\Accounting\Database\Seeders;

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
        $this->command->info('Seeding Accounting permissions...');

        // Define all Accounting module entities
        $entities = [
            'accounts',
            'account-balances',
            'account-mappings',
            'fiscal-periods',
            'journals',
            'journal-sequences',
            'journal-entries',
            'journal-lines',
            'exchange-rates',
            'exchange-rate-policies',
            'audit-logs',
            'idempotency-keys',
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
            $this->command->warn("Assigning Accounting permissions to {$god->name} role...");
            $permissions = Permission::where(function($query) use ($entities) {
                foreach ($entities as $entity) {
                    $query->orWhere('name', 'like', "{$entity}.%");
                }
            })->get();
            $god->givePermissionTo($permissions);
            $this->command->info("God role assigned all Accounting permissions");
        }

        // Assign permissions to Admin role (all permissions)
        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $this->command->warn("Assigning Accounting permissions to {$admin->name} role...");
            foreach ($entities as $entity) {
                $admin->givePermissionTo([
                    "{$entity}.index",
                    "{$entity}.show",
                    "{$entity}.store",
                    "{$entity}.update",
                    "{$entity}.destroy",
                ]);
            }
            $this->command->info("Admin role assigned all Accounting permissions");
        }

        // Assign read-only permissions to Tech role
        $tech = Role::where('name', 'tech')->first();
        if ($tech) {
            $this->command->warn("Assigning read-only Accounting permissions to {$tech->name} role...");
            foreach ($entities as $entity) {
                $tech->givePermissionTo([
                    "{$entity}.index",
                    "{$entity}.show",
                ]);
            }
            $this->command->info("Tech role assigned read-only Accounting permissions");
        }

        // Customer role has no accounting access by design
        $customer = Role::where('name', 'customer')->first();
        if ($customer) {
            $this->command->info("Customer role has no Accounting permissions (as expected)");
        }

        $this->command->info('Accounting permissions seeded successfully!');
    }
}
