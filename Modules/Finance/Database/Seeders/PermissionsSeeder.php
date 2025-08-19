<?php

namespace Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🔐 Seeding Finance permissions...');
        
        // Create permissions
        Permission::firstOrCreate([
            'name' => 'finance.bank-accounts.index',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.bank-accounts.show',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.bank-accounts.store',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.bank-accounts.update',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.bank-accounts.destroy',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.bank-statements.index',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.bank-statements.show',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.bank-statements.store',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.bank-statements.update',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.bank-statements.destroy',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.bank-statement-lines.index',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.bank-statement-lines.show',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.bank-statement-lines.store',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.bank-statement-lines.update',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.bank-statement-lines.destroy',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.ap-invoices.index',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.ap-invoices.show',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.ap-invoices.store',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.ap-invoices.update',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.ap-invoices.destroy',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.ap-invoice-lines.index',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.ap-invoice-lines.show',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.ap-invoice-lines.store',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.ap-invoice-lines.update',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.ap-invoice-lines.destroy',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.ap-payments.index',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.ap-payments.show',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.ap-payments.store',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.ap-payments.update',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.ap-payments.destroy',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.ap-invoice-payments.index',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.ap-invoice-payments.show',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.ap-invoice-payments.store',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.ap-invoice-payments.update',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.ap-invoice-payments.destroy',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.ar-invoices.index',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.ar-invoices.show',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.ar-invoices.store',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.ar-invoices.update',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.ar-invoices.destroy',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.ar-invoice-lines.index',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.ar-invoice-lines.show',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.ar-invoice-lines.store',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.ar-invoice-lines.update',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.ar-invoice-lines.destroy',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.ar-receipts.index',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.ar-receipts.show',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.ar-receipts.store',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.ar-receipts.update',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.ar-receipts.destroy',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.ar-invoice-receipts.index',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.ar-invoice-receipts.show',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.ar-invoice-receipts.store',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.ar-invoice-receipts.update',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'finance.ar-invoice-receipts.destroy',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'bank-accounts.index',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'bank-accounts.show',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'bank-accounts.store',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'bank-accounts.update',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ap-invoices.index',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ap-invoices.show',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ap-invoices.store',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ap-invoices.update',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ar-invoices.index',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ar-invoices.show',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ar-invoices.store',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ar-invoices.update',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ap-payments.index',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ap-payments.show',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ap-payments.store',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ap-payments.update',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ar-receipts.index',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ar-receipts.show',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ar-receipts.store',
            'guard_name' => 'api',
        ]);
        Permission::firstOrCreate([
            'name' => 'ar-receipts.update',
            'guard_name' => 'api',
        ]);
        
        // Assign permissions to roles

        // god role permissions
        $rolegod = Role::where('name', 'god')->where('guard_name', 'api')->first();
        if ($rolegod) {
            $rolegod->givePermissionTo('finance.bank-accounts.index');
            $rolegod->givePermissionTo('finance.bank-accounts.show');
            $rolegod->givePermissionTo('finance.bank-accounts.store');
            $rolegod->givePermissionTo('finance.bank-accounts.update');
            $rolegod->givePermissionTo('finance.bank-accounts.destroy');
            $rolegod->givePermissionTo('finance.bank-statements.index');
            $rolegod->givePermissionTo('finance.bank-statements.show');
            $rolegod->givePermissionTo('finance.bank-statements.store');
            $rolegod->givePermissionTo('finance.bank-statements.update');
            $rolegod->givePermissionTo('finance.bank-statements.destroy');
            $rolegod->givePermissionTo('finance.bank-statement-lines.index');
            $rolegod->givePermissionTo('finance.bank-statement-lines.show');
            $rolegod->givePermissionTo('finance.bank-statement-lines.store');
            $rolegod->givePermissionTo('finance.bank-statement-lines.update');
            $rolegod->givePermissionTo('finance.bank-statement-lines.destroy');
            $rolegod->givePermissionTo('finance.ap-invoices.index');
            $rolegod->givePermissionTo('finance.ap-invoices.show');
            $rolegod->givePermissionTo('finance.ap-invoices.store');
            $rolegod->givePermissionTo('finance.ap-invoices.update');
            $rolegod->givePermissionTo('finance.ap-invoices.destroy');
            $rolegod->givePermissionTo('finance.ap-invoice-lines.index');
            $rolegod->givePermissionTo('finance.ap-invoice-lines.show');
            $rolegod->givePermissionTo('finance.ap-invoice-lines.store');
            $rolegod->givePermissionTo('finance.ap-invoice-lines.update');
            $rolegod->givePermissionTo('finance.ap-invoice-lines.destroy');
            $rolegod->givePermissionTo('finance.ap-payments.index');
            $rolegod->givePermissionTo('finance.ap-payments.show');
            $rolegod->givePermissionTo('finance.ap-payments.store');
            $rolegod->givePermissionTo('finance.ap-payments.update');
            $rolegod->givePermissionTo('finance.ap-payments.destroy');
            $rolegod->givePermissionTo('finance.ap-invoice-payments.index');
            $rolegod->givePermissionTo('finance.ap-invoice-payments.show');
            $rolegod->givePermissionTo('finance.ap-invoice-payments.store');
            $rolegod->givePermissionTo('finance.ap-invoice-payments.update');
            $rolegod->givePermissionTo('finance.ap-invoice-payments.destroy');
            $rolegod->givePermissionTo('finance.ar-invoices.index');
            $rolegod->givePermissionTo('finance.ar-invoices.show');
            $rolegod->givePermissionTo('finance.ar-invoices.store');
            $rolegod->givePermissionTo('finance.ar-invoices.update');
            $rolegod->givePermissionTo('finance.ar-invoices.destroy');
            $rolegod->givePermissionTo('finance.ar-invoice-lines.index');
            $rolegod->givePermissionTo('finance.ar-invoice-lines.show');
            $rolegod->givePermissionTo('finance.ar-invoice-lines.store');
            $rolegod->givePermissionTo('finance.ar-invoice-lines.update');
            $rolegod->givePermissionTo('finance.ar-invoice-lines.destroy');
            $rolegod->givePermissionTo('finance.ar-receipts.index');
            $rolegod->givePermissionTo('finance.ar-receipts.show');
            $rolegod->givePermissionTo('finance.ar-receipts.store');
            $rolegod->givePermissionTo('finance.ar-receipts.update');
            $rolegod->givePermissionTo('finance.ar-receipts.destroy');
            $rolegod->givePermissionTo('finance.ar-invoice-receipts.index');
            $rolegod->givePermissionTo('finance.ar-invoice-receipts.show');
            $rolegod->givePermissionTo('finance.ar-invoice-receipts.store');
            $rolegod->givePermissionTo('finance.ar-invoice-receipts.update');
            $rolegod->givePermissionTo('finance.ar-invoice-receipts.destroy');
        }

        // admin role permissions
        $roleadmin = Role::where('name', 'admin')->where('guard_name', 'api')->first();
        if ($roleadmin) {
            $roleadmin->givePermissionTo('finance.bank-accounts.index');
            $roleadmin->givePermissionTo('finance.bank-accounts.show');
            $roleadmin->givePermissionTo('finance.bank-accounts.store');
            $roleadmin->givePermissionTo('finance.bank-accounts.update');
            $roleadmin->givePermissionTo('finance.bank-accounts.destroy');
            $roleadmin->givePermissionTo('finance.bank-statements.index');
            $roleadmin->givePermissionTo('finance.bank-statements.show');
            $roleadmin->givePermissionTo('finance.bank-statements.store');
            $roleadmin->givePermissionTo('finance.bank-statements.update');
            $roleadmin->givePermissionTo('finance.bank-statements.destroy');
            $roleadmin->givePermissionTo('finance.bank-statement-lines.index');
            $roleadmin->givePermissionTo('finance.bank-statement-lines.show');
            $roleadmin->givePermissionTo('finance.bank-statement-lines.store');
            $roleadmin->givePermissionTo('finance.bank-statement-lines.update');
            $roleadmin->givePermissionTo('finance.bank-statement-lines.destroy');
            $roleadmin->givePermissionTo('finance.ap-invoices.index');
            $roleadmin->givePermissionTo('finance.ap-invoices.show');
            $roleadmin->givePermissionTo('finance.ap-invoices.store');
            $roleadmin->givePermissionTo('finance.ap-invoices.update');
            $roleadmin->givePermissionTo('finance.ap-invoices.destroy');
            $roleadmin->givePermissionTo('finance.ap-invoice-lines.index');
            $roleadmin->givePermissionTo('finance.ap-invoice-lines.show');
            $roleadmin->givePermissionTo('finance.ap-invoice-lines.store');
            $roleadmin->givePermissionTo('finance.ap-invoice-lines.update');
            $roleadmin->givePermissionTo('finance.ap-invoice-lines.destroy');
            $roleadmin->givePermissionTo('finance.ap-payments.index');
            $roleadmin->givePermissionTo('finance.ap-payments.show');
            $roleadmin->givePermissionTo('finance.ap-payments.store');
            $roleadmin->givePermissionTo('finance.ap-payments.update');
            $roleadmin->givePermissionTo('finance.ap-payments.destroy');
            $roleadmin->givePermissionTo('finance.ap-invoice-payments.index');
            $roleadmin->givePermissionTo('finance.ap-invoice-payments.show');
            $roleadmin->givePermissionTo('finance.ap-invoice-payments.store');
            $roleadmin->givePermissionTo('finance.ap-invoice-payments.update');
            $roleadmin->givePermissionTo('finance.ap-invoice-payments.destroy');
            $roleadmin->givePermissionTo('finance.ar-invoices.index');
            $roleadmin->givePermissionTo('finance.ar-invoices.show');
            $roleadmin->givePermissionTo('finance.ar-invoices.store');
            $roleadmin->givePermissionTo('finance.ar-invoices.update');
            $roleadmin->givePermissionTo('finance.ar-invoices.destroy');
            $roleadmin->givePermissionTo('finance.ar-invoice-lines.index');
            $roleadmin->givePermissionTo('finance.ar-invoice-lines.show');
            $roleadmin->givePermissionTo('finance.ar-invoice-lines.store');
            $roleadmin->givePermissionTo('finance.ar-invoice-lines.update');
            $roleadmin->givePermissionTo('finance.ar-invoice-lines.destroy');
            $roleadmin->givePermissionTo('finance.ar-receipts.index');
            $roleadmin->givePermissionTo('finance.ar-receipts.show');
            $roleadmin->givePermissionTo('finance.ar-receipts.store');
            $roleadmin->givePermissionTo('finance.ar-receipts.update');
            $roleadmin->givePermissionTo('finance.ar-receipts.destroy');
            $roleadmin->givePermissionTo('finance.ar-invoice-receipts.index');
            $roleadmin->givePermissionTo('finance.ar-invoice-receipts.show');
            $roleadmin->givePermissionTo('finance.ar-invoice-receipts.store');
            $roleadmin->givePermissionTo('finance.ar-invoice-receipts.update');
            $roleadmin->givePermissionTo('finance.ar-invoice-receipts.destroy');
        }

        // finance_manager role permissions
        $rolefinance_manager = Role::where('name', 'finance_manager')->where('guard_name', 'api')->first();
        if ($rolefinance_manager) {
            $rolefinance_manager->givePermissionTo('bank-accounts.index');
            $rolefinance_manager->givePermissionTo('bank-accounts.show');
            $rolefinance_manager->givePermissionTo('bank-accounts.store');
            $rolefinance_manager->givePermissionTo('bank-accounts.update');
            $rolefinance_manager->givePermissionTo('ap-invoices.index');
            $rolefinance_manager->givePermissionTo('ap-invoices.show');
            $rolefinance_manager->givePermissionTo('ap-invoices.store');
            $rolefinance_manager->givePermissionTo('ap-invoices.update');
            $rolefinance_manager->givePermissionTo('ar-invoices.index');
            $rolefinance_manager->givePermissionTo('ar-invoices.show');
            $rolefinance_manager->givePermissionTo('ar-invoices.store');
            $rolefinance_manager->givePermissionTo('ar-invoices.update');
            $rolefinance_manager->givePermissionTo('ap-payments.index');
            $rolefinance_manager->givePermissionTo('ap-payments.show');
            $rolefinance_manager->givePermissionTo('ap-payments.store');
            $rolefinance_manager->givePermissionTo('ap-payments.update');
            $rolefinance_manager->givePermissionTo('ar-receipts.index');
            $rolefinance_manager->givePermissionTo('ar-receipts.show');
            $rolefinance_manager->givePermissionTo('ar-receipts.store');
            $rolefinance_manager->givePermissionTo('ar-receipts.update');
        }

        // tech role permissions
        $roletech = Role::where('name', 'tech')->where('guard_name', 'api')->first();
        if ($roletech) {
            $roletech->givePermissionTo('bank-accounts.index');
            $roletech->givePermissionTo('bank-accounts.show');
            $roletech->givePermissionTo('ap-invoices.index');
            $roletech->givePermissionTo('ap-invoices.show');
            $roletech->givePermissionTo('ar-invoices.index');
            $roletech->givePermissionTo('ar-invoices.show');
        }

        // customer role permissions
        $rolecustomer = Role::where('name', 'customer')->where('guard_name', 'api')->first();
        if ($rolecustomer) {
        }
        
        $this->command->info('✅ Finance permissions seeded successfully!');
    }
}
