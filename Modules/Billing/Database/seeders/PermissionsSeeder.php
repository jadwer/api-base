<?php

namespace Modules\Billing\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Database\Seeders\Concerns\BulkPermissions;

class PermissionsSeeder extends Seeder
{
    use BulkPermissions;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔐 Seeding permissions...');

        // Read-only permissions for Admin, Tech, and Customer
        $readPermissions = [
            'billing.cfdi-invoices.index',
            'billing.cfdi-invoices.show',
            'billing.cfdi-items.index',
            'billing.cfdi-items.show',
            'billing.payment-transactions.index',
            'billing.payment-transactions.show',
            'billing.cfdi-invoices.generate-xml',
            'billing.cfdi-invoices.generate-pdf',
            'billing.cfdi-invoices.download-xml',
            'billing.cfdi-invoices.download-pdf',
            'billing.cfdi-invoices.preview-pdf',
            'billing.cfdi-invoices.validate',
            'billing.cfdi-invoices.cancellation-status',
        ];

        $this->bulkCreateAndAssignPermissions($readPermissions, ['admin', 'tech', 'customer']);

        // Write permissions for Admin only (create, update, delete)
        $writePermissions = [
            'billing.cfdi-invoices.store',
            'billing.cfdi-invoices.update',
            'billing.cfdi-invoices.destroy',
            'billing.cfdi-items.store',
            'billing.cfdi-items.update',
            'billing.cfdi-items.destroy',
            'billing.payment-transactions.store',
            'billing.payment-transactions.update',
            'billing.payment-transactions.destroy',
            'billing.cfdi-invoices.stamp',
            'billing.cfdi-invoices.cancel',
        ];

        $this->bulkCreateAndAssignPermissions($writePermissions, ['admin']);

        // Company settings permissions (Admin only)
        $settingsPermissions = [
            'billing.company-settings.index',
            'billing.company-settings.show',
            'billing.company-settings.store',
            'billing.company-settings.update',
            'billing.company-settings.destroy',
        ];

        $this->bulkCreateAndAssignPermissions($settingsPermissions, ['admin']);

        $this->command->info('✅ Permissions seeded successfully!');
    }
}
