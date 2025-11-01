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
        // CFDI GENERATION PERMISSIONS (Phase 2 - CFDI Operations)
        // =========================================================================
        Permission::firstOrCreate(['name' => 'billing.cfdi-invoices.generate-xml', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'billing.cfdi-invoices.generate-pdf', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'billing.cfdi-invoices.download-xml', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'billing.cfdi-invoices.download-pdf', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'billing.cfdi-invoices.preview-pdf', 'guard_name' => 'api']);

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
            'billing.cfdi-invoices.generate-xml',
            'billing.cfdi-invoices.generate-pdf',
            'billing.cfdi-invoices.download-xml',
            'billing.cfdi-invoices.download-pdf',
            'billing.cfdi-invoices.preview-pdf',
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
            'billing.cfdi-invoices.generate-xml',
            'billing.cfdi-invoices.generate-pdf',
            'billing.cfdi-invoices.download-xml',
            'billing.cfdi-invoices.download-pdf',
            'billing.cfdi-invoices.preview-pdf',
            'billing.cfdi-items.index',
            'billing.cfdi-items.show',
            'billing.cfdi-items.store',
            'billing.cfdi-items.update',
            'billing.cfdi-items.destroy',
        ]);

        // TECH role - Full CRUD for transactions, Read-only for company settings, invoices, and items, can view/download CFDI files
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
            'billing.cfdi-invoices.download-xml',
            'billing.cfdi-invoices.download-pdf',
            'billing.cfdi-invoices.preview-pdf',
            'billing.cfdi-items.index',
            'billing.cfdi-items.show',
        ]);

        // CUSTOMER role - Read-only for transactions, invoices, and items, can download their own CFDI files
        $rolecustomer->givePermissionTo([
            'billing.payment-transactions.index',
            'billing.payment-transactions.show',
            'billing.cfdi-invoices.index',
            'billing.cfdi-invoices.show',
            'billing.cfdi-invoices.download-xml',
            'billing.cfdi-invoices.download-pdf',
            'billing.cfdi-invoices.preview-pdf',
            'billing.cfdi-items.index',
            'billing.cfdi-items.show',
        ]);

        $this->command->info('✅ Billing Module Permissions created successfully');
        $this->command->info('   - God role: 25 permissions (full CRUD + CFDI operations)');
        $this->command->info('   - Admin role: 25 permissions (full CRUD + CFDI operations)');
        $this->command->info('   - Tech role: 14 permissions (full CRUD transactions, read + download CFDI)');
        $this->command->info('   - Customer role: 9 permissions (read + download own CFDI)');
    }
}
