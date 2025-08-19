<?php

namespace Modules\Accounting\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🔐 Seeding Accounting permissions...');
        
        // Create permissions
        Permission::firstOrCreate([
            'name' => 'accounting.accounts.index',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'accounting.accounts.show',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'accounting.accounts.store',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'accounting.accounts.update',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'accounting.accounts.destroy',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'accounting.fiscal-periods.index',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'accounting.fiscal-periods.show',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'accounting.fiscal-periods.store',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'accounting.fiscal-periods.update',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'accounting.fiscal-periods.destroy',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'accounting.journals.index',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'accounting.journals.show',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'accounting.journals.store',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'accounting.journals.update',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'accounting.journals.destroy',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'accounting.journal-entries.index',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'accounting.journal-entries.show',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'accounting.journal-entries.store',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'accounting.journal-entries.update',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'accounting.journal-entries.destroy',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'accounting.journal-lines.index',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'accounting.journal-lines.show',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'accounting.journal-lines.store',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'accounting.journal-lines.update',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'accounting.journal-lines.destroy',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'accounting.exchange-rates.index',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'accounting.exchange-rates.show',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'accounting.exchange-rates.store',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'accounting.exchange-rates.update',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'accounting.exchange-rates.destroy',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'accounts.index',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'accounts.show',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'accounts.store',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'accounts.update',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'fiscal-periods.index',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'fiscal-periods.show',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'journals.index',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'journals.show',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'journals.store',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'journals.update',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'journal-entries.index',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'journal-entries.show',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'journal-entries.store',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'journal-entries.update',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'journal-lines.index',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'journal-lines.show',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'journal-lines.store',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'journal-lines.update',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'exchange-rates.index',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'exchange-rates.show',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'exchange-rates.store',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'exchange-rates.update',
            'guard_name' => 'api',
        ]);
        
        // Assign permissions to roles

        // god role permissions
        $rolegod = Role::where('name', 'god')->where('guard_name', 'api')->first();
        if ($rolegod) {
            $rolegod->givePermissionTo('accounting.accounts.index');
            $rolegod->givePermissionTo('accounting.accounts.show');
            $rolegod->givePermissionTo('accounting.accounts.store');
            $rolegod->givePermissionTo('accounting.accounts.update');
            $rolegod->givePermissionTo('accounting.accounts.destroy');
            $rolegod->givePermissionTo('accounting.fiscal-periods.index');
            $rolegod->givePermissionTo('accounting.fiscal-periods.show');
            $rolegod->givePermissionTo('accounting.fiscal-periods.store');
            $rolegod->givePermissionTo('accounting.fiscal-periods.update');
            $rolegod->givePermissionTo('accounting.fiscal-periods.destroy');
            $rolegod->givePermissionTo('accounting.journals.index');
            $rolegod->givePermissionTo('accounting.journals.show');
            $rolegod->givePermissionTo('accounting.journals.store');
            $rolegod->givePermissionTo('accounting.journals.update');
            $rolegod->givePermissionTo('accounting.journals.destroy');
            $rolegod->givePermissionTo('accounting.journal-entries.index');
            $rolegod->givePermissionTo('accounting.journal-entries.show');
            $rolegod->givePermissionTo('accounting.journal-entries.store');
            $rolegod->givePermissionTo('accounting.journal-entries.update');
            $rolegod->givePermissionTo('accounting.journal-entries.destroy');
            $rolegod->givePermissionTo('accounting.journal-lines.index');
            $rolegod->givePermissionTo('accounting.journal-lines.show');
            $rolegod->givePermissionTo('accounting.journal-lines.store');
            $rolegod->givePermissionTo('accounting.journal-lines.update');
            $rolegod->givePermissionTo('accounting.journal-lines.destroy');
            $rolegod->givePermissionTo('accounting.exchange-rates.index');
            $rolegod->givePermissionTo('accounting.exchange-rates.show');
            $rolegod->givePermissionTo('accounting.exchange-rates.store');
            $rolegod->givePermissionTo('accounting.exchange-rates.update');
            $rolegod->givePermissionTo('accounting.exchange-rates.destroy');
        }

        // admin role permissions
        $roleadmin = Role::where('name', 'admin')->where('guard_name', 'api')->first();
        if ($roleadmin) {
            $roleadmin->givePermissionTo('accounting.accounts.index');
            $roleadmin->givePermissionTo('accounting.accounts.show');
            $roleadmin->givePermissionTo('accounting.accounts.store');
            $roleadmin->givePermissionTo('accounting.accounts.update');
            $roleadmin->givePermissionTo('accounting.accounts.destroy');
            $roleadmin->givePermissionTo('accounting.fiscal-periods.index');
            $roleadmin->givePermissionTo('accounting.fiscal-periods.show');
            $roleadmin->givePermissionTo('accounting.fiscal-periods.store');
            $roleadmin->givePermissionTo('accounting.fiscal-periods.update');
            $roleadmin->givePermissionTo('accounting.fiscal-periods.destroy');
            $roleadmin->givePermissionTo('accounting.journals.index');
            $roleadmin->givePermissionTo('accounting.journals.show');
            $roleadmin->givePermissionTo('accounting.journals.store');
            $roleadmin->givePermissionTo('accounting.journals.update');
            $roleadmin->givePermissionTo('accounting.journals.destroy');
            $roleadmin->givePermissionTo('accounting.journal-entries.index');
            $roleadmin->givePermissionTo('accounting.journal-entries.show');
            $roleadmin->givePermissionTo('accounting.journal-entries.store');
            $roleadmin->givePermissionTo('accounting.journal-entries.update');
            $roleadmin->givePermissionTo('accounting.journal-entries.destroy');
            $roleadmin->givePermissionTo('accounting.journal-lines.index');
            $roleadmin->givePermissionTo('accounting.journal-lines.show');
            $roleadmin->givePermissionTo('accounting.journal-lines.store');
            $roleadmin->givePermissionTo('accounting.journal-lines.update');
            $roleadmin->givePermissionTo('accounting.journal-lines.destroy');
            $roleadmin->givePermissionTo('accounting.exchange-rates.index');
            $roleadmin->givePermissionTo('accounting.exchange-rates.show');
            $roleadmin->givePermissionTo('accounting.exchange-rates.store');
            $roleadmin->givePermissionTo('accounting.exchange-rates.update');
            $roleadmin->givePermissionTo('accounting.exchange-rates.destroy');
        }

        // accountant role permissions
        $roleaccountant = Role::where('name', 'accountant')->where('guard_name', 'api')->first();
        if ($roleaccountant) {
            $roleaccountant->givePermissionTo('accounts.index');
            $roleaccountant->givePermissionTo('accounts.show');
            $roleaccountant->givePermissionTo('accounts.store');
            $roleaccountant->givePermissionTo('accounts.update');
            $roleaccountant->givePermissionTo('fiscal-periods.index');
            $roleaccountant->givePermissionTo('fiscal-periods.show');
            $roleaccountant->givePermissionTo('journals.index');
            $roleaccountant->givePermissionTo('journals.show');
            $roleaccountant->givePermissionTo('journals.store');
            $roleaccountant->givePermissionTo('journals.update');
            $roleaccountant->givePermissionTo('journal-entries.index');
            $roleaccountant->givePermissionTo('journal-entries.show');
            $roleaccountant->givePermissionTo('journal-entries.store');
            $roleaccountant->givePermissionTo('journal-entries.update');
            $roleaccountant->givePermissionTo('journal-lines.index');
            $roleaccountant->givePermissionTo('journal-lines.show');
            $roleaccountant->givePermissionTo('journal-lines.store');
            $roleaccountant->givePermissionTo('journal-lines.update');
            $roleaccountant->givePermissionTo('exchange-rates.index');
            $roleaccountant->givePermissionTo('exchange-rates.show');
            $roleaccountant->givePermissionTo('exchange-rates.store');
            $roleaccountant->givePermissionTo('exchange-rates.update');
        }

        // tech role permissions
        $roletech = Role::where('name', 'tech')->where('guard_name', 'api')->first();
        if ($roletech) {
            $roletech->givePermissionTo('accounting.accounts.index');
            $roletech->givePermissionTo('accounting.accounts.show');
            $roletech->givePermissionTo('accounting.journal-entries.index');
            $roletech->givePermissionTo('accounting.journal-entries.show');
            $roletech->givePermissionTo('accounting.journal-lines.index');
            $roletech->givePermissionTo('accounting.journal-lines.show');
        }

        // customer role permissions
        $rolecustomer = Role::where('name', 'customer')->where('guard_name', 'api')->first();
        if ($rolecustomer) {
        }
        
        $this->command->info('✅ Accounting permissions seeded successfully!');
    }
}
