<?php

namespace Modules\Billing\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create roles
        $rolegod = Role::firstOrCreate(['name' => 'god', 'guard_name' => 'api']);
        $roleadmin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $roletech = Role::firstOrCreate(['name' => 'tech', 'guard_name' => 'api']);
        $rolecustomer = Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'api']);

        // =========================================================================
        // PAYMENT TRANSACTIONS PERMISSIONS (Phase 1)
        // =========================================================================
        Permission::firstOrCreate(['name' => 'billing.payment-transactions.index', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'billing.payment-transactions.show', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'billing.payment-transactions.store', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'billing.payment-transactions.update', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'billing.payment-transactions.destroy', 'guard_name' => 'api']);

        // =========================================================================
        // COMPANY SETTINGS PERMISSIONS (Phase 2 - placeholder)
        // =========================================================================
        // Permission::firstOrCreate(['name' => 'billing.company-settings.index', 'guard_name' => 'api']);
        // Permission::firstOrCreate(['name' => 'billing.company-settings.show', 'guard_name' => 'api']);
        // Permission::firstOrCreate(['name' => 'billing.company-settings.store', 'guard_name' => 'api']);
        // Permission::firstOrCreate(['name' => 'billing.company-settings.update', 'guard_name' => 'api']);
        // Permission::firstOrCreate(['name' => 'billing.company-settings.destroy', 'guard_name' => 'api']);

        // =========================================================================
        // CFDI INVOICES PERMISSIONS (Phase 2 - placeholder)
        // =========================================================================
        // Permission::firstOrCreate(['name' => 'billing.cfdi-invoices.index', 'guard_name' => 'api']);
        // Permission::firstOrCreate(['name' => 'billing.cfdi-invoices.show', 'guard_name' => 'api']);
        // Permission::firstOrCreate(['name' => 'billing.cfdi-invoices.store', 'guard_name' => 'api']);
        // Permission::firstOrCreate(['name' => 'billing.cfdi-invoices.update', 'guard_name' => 'api']);
        // Permission::firstOrCreate(['name' => 'billing.cfdi-invoices.destroy', 'guard_name' => 'api']);

        // =========================================================================
        // ASSIGN PERMISSIONS TO ROLES
        // =========================================================================

        // GOD role - Full permissions
        $rolegod->givePermissionTo([
            'billing.payment-transactions.index',
            'billing.payment-transactions.show',
            'billing.payment-transactions.store',
            'billing.payment-transactions.update',
            'billing.payment-transactions.destroy',
        ]);

        // ADMIN role - Full permissions
        $roleadmin->givePermissionTo([
            'billing.payment-transactions.index',
            'billing.payment-transactions.show',
            'billing.payment-transactions.store',
            'billing.payment-transactions.update',
            'billing.payment-transactions.destroy',
        ]);

        // TECH role - Full CRUD (consistent with Sales/Purchase modules)
        $roletech->givePermissionTo([
            'billing.payment-transactions.index',
            'billing.payment-transactions.show',
            'billing.payment-transactions.store',
            'billing.payment-transactions.update',
            'billing.payment-transactions.destroy',
        ]);

        // CUSTOMER role - Read-only for their own transactions
        $rolecustomer->givePermissionTo([
            'billing.payment-transactions.index',
            'billing.payment-transactions.show',
        ]);

        $this->command->info('✅ Billing Module Permissions created successfully');
        $this->command->info('   - God role: 5 permissions (full CRUD)');
        $this->command->info('   - Admin role: 5 permissions (full CRUD)');
        $this->command->info('   - Tech role: 5 permissions (full CRUD)');
        $this->command->info('   - Customer role: 2 permissions (read-only)');
    }
}
