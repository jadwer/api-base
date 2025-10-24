<?php

namespace Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class FinanceAssignPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $god = Role::where('name', 'god')->first();

        if ($god) {
            $this->command->warn("Asignando permisos de Finance al rol {$god->name}, sin sobrescribir los existentes...");
            $permissions = Permission::where('name', 'like', 'ar-invoices.%')
                ->orWhere('name', 'like', 'ap-invoices.%')
                ->orWhere('name', 'like', 'payments.%')
                ->orWhere('name', 'like', 'payment-applications.%')
                ->orWhere('name', 'like', 'bank-accounts.%')
                ->orWhere('name', 'like', 'payment-methods.%')
                ->get();
            $god->givePermissionTo($permissions);
        }

        $admin = Role::where('name', 'admin')->first();

        if ($admin) {
            $admin->givePermissionTo([
                // AR Invoices - Full access
                'ar-invoices.index',
                'ar-invoices.show',
                'ar-invoices.store',
                'ar-invoices.update',
                'ar-invoices.destroy',

                // AP Invoices - Full access
                'ap-invoices.index',
                'ap-invoices.show',
                'ap-invoices.store',
                'ap-invoices.update',
                'ap-invoices.destroy',

                // Payments - Full access
                'payments.index',
                'payments.show',
                'payments.store',
                'payments.update',
                'payments.destroy',

                // Payment Applications - Full access
                'payment-applications.index',
                'payment-applications.show',
                'payment-applications.store',
                'payment-applications.update',
                'payment-applications.destroy',

                // Bank Accounts - Full access
                'bank-accounts.index',
                'bank-accounts.show',
                'bank-accounts.store',
                'bank-accounts.update',
                'bank-accounts.destroy',

                // Payment Methods - Full access
                'payment-methods.index',
                'payment-methods.show',
                'payment-methods.store',
                'payment-methods.update',
                'payment-methods.destroy',
            ]);
        }

        // Tech role - Read only
        $tech = Role::where('name', 'tech')->first();
        if ($tech) {
            $tech->givePermissionTo([
                'ar-invoices.index',
                'ar-invoices.show',
                'ap-invoices.index',
                'ap-invoices.show',
                'payments.index',
                'payments.show',
                'payment-applications.index',
                'payment-applications.show',
                'bank-accounts.index',
                'bank-accounts.show',
                'payment-methods.index',
                'payment-methods.show',
            ]);
        }
    }
}
