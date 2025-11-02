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

        $permissions = [
            'god',
            'admin',
            'tech',
            'customer',
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
            'billing.cfdi-invoices.generate-xml',
            'billing.cfdi-invoices.generate-pdf',
            'billing.cfdi-invoices.download-xml',
            'billing.cfdi-invoices.download-pdf',
            'billing.cfdi-invoices.preview-pdf',
        ];

        $this->bulkCreatePermissions($permissions);

        $this->command->info('✅ Permissions seeded successfully!');
    }
}
