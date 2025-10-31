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
        // COMPANY SETTINGS PERMISSIONS (Phase 2)
        // =========================================================================
        Permission::firstOrCreate(['name' => 'billing.company-settings.index', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'billing.company-settings.show', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'billing.company-settings.store', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'billing.company-settings.update', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'billing.company-settings.destroy', 'guard_name' => 'api']);

        // =========================================================================
        // CFDI INVOICES PERMISSIONS (Phase 2)
        // =========================================================================
        Permission::firstOrCreate(['name' => 'billing.cfdi-invoices.index', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'billing.cfdi-invoices.show', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'billing.cfdi-invoices.store', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'billing.cfdi-invoices.update', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'billing.cfdi-invoices.destroy', 'guard_name' => 'api']);

        // =========================================================================
        // CFDI ITEMS PERMISSIONS (Phase 2)
        // =========================================================================
        Permission::firstOrCreate(['name' => 'billing.cfdi-items.index', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'billing.cfdi-items.show', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'billing.cfdi-items.store', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'billing.cfdi-items.update', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'billing.cfdi-items.destroy', 'guard_name' => 'api']);

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
            'billing.company-settings.index',
            'billing.company-settings.show',
            'billing.company-settings.store',
            'billing.company-settings.update',
            'billing.company-settings.destroy',
            'billing.cfdi-invoices.index',
            'billing.cfdi-invoices.show',
            'billing.cfdi-invoices.store',
            'billing.cfdi-invoices.update',
            'billing.cfdi-invoices.destroy',
            'billing.cfdi-items.index',
            'billing.cfdi-items.show',
            'billing.cfdi-items.store',
            'billing.cfdi-items.update',
            'billing.cfdi-items.destroy',
        ]);

        // ADMIN role - Full permissions
        $roleadmin->givePermissionTo([
            'billing.payment-transactions.index',
            'billing.payment-transactions.show',
            'billing.payment-transactions.store',
            'billing.payment-transactions.update',
            'billing.payment-transactions.destroy',
            'billing.company-settings.index',
            'billing.company-settings.show',
            'billing.company-settings.store',
            'billing.company-settings.update',
            'billing.company-settings.destroy',
            'billing.cfdi-invoices.index',
            'billing.cfdi-invoices.show',
            'billing.cfdi-invoices.store',
            'billing.cfdi-invoices.update',
            'billing.cfdi-invoices.destroy',
            'billing.cfdi-items.index',
            'billing.cfdi-items.show',
            'billing.cfdi-items.store',
            'billing.cfdi-items.update',
            'billing.cfdi-items.destroy',
        ]);

        // TECH role - Full CRUD for transactions, Read-only for company settings, invoices, and items
        $roletech->givePermissionTo([
            'billing.payment-transactions.index',
            'billing.payment-transactions.show',
            'billing.payment-transactions.store',
            'billing.payment-transactions.update',
            'billing.payment-transactions.destroy',
            'billing.company-settings.index',
            'billing.company-settings.show',
            'billing.cfdi-invoices.index',
            'billing.cfdi-invoices.show',
            'billing.cfdi-items.index',
            'billing.cfdi-items.show',
        ]);

        // CUSTOMER role - Read-only for transactions, invoices, and items, no access to company settings
        $rolecustomer->givePermissionTo([
            'billing.payment-transactions.index',
            'billing.payment-transactions.show',
            'billing.cfdi-invoices.index',
            'billing.cfdi-invoices.show',
            'billing.cfdi-items.index',
            'billing.cfdi-items.show',
        ]);

        $this->command->info('✅ Billing Module Permissions created successfully');
        $this->command->info('   - God role: 20 permissions (full CRUD on 4 entities)');
        $this->command->info('   - Admin role: 20 permissions (full CRUD on 4 entities)');
        $this->command->info('   - Tech role: 11 permissions (full CRUD transactions, read-only settings/invoices/items)');
        $this->command->info('   - Customer role: 6 permissions (read-only transactions/invoices/items)');
    }
}
