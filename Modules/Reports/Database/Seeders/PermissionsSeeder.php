<?php

namespace Modules\Reports\Database\Seeders;

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
        $this->command->info('🔐 Seeding Reports permissions...');

        // Financial statement report permissions (admin and tech can view)
        $financialReportPermissions = [
            // Balance Sheets
            'reports.balance-sheets.index',
            'reports.balance-sheets.show',

            // Income Statements
            'reports.income-statements.index',
            'reports.income-statements.show',

            // Cash Flow Statements
            'reports.cash-flows.index',
            'reports.cash-flows.show',

            // Trial Balances
            'reports.trial-balances.index',
            'reports.trial-balances.show',
        ];

        $this->bulkCreateAndAssignPermissions($financialReportPermissions, ['admin', 'tech']);

        // Aging report permissions (admin and tech can view)
        $agingReportPermissions = [
            // AP Aging
            'reports.ap-aging-reports.index',
            'reports.ap-aging-reports.show',

            // AR Aging
            'reports.ar-aging-reports.index',
            'reports.ar-aging-reports.show',
        ];

        $this->bulkCreateAndAssignPermissions($agingReportPermissions, ['admin', 'tech']);

        // Sales report permissions (admin and tech can view)
        $salesReportPermissions = [
            // Sales by Customer
            'reports.sales-by-customer-reports.index',
            'reports.sales-by-customer-reports.show',

            // Sales by Product
            'reports.sales-by-product-reports.index',
            'reports.sales-by-product-reports.show',
        ];

        $this->bulkCreateAndAssignPermissions($salesReportPermissions, ['admin', 'tech']);

        // Purchase report permissions (admin and tech can view)
        $purchaseReportPermissions = [
            // Purchase by Supplier
            'reports.purchase-by-supplier-reports.index',
            'reports.purchase-by-supplier-reports.show',

            // Purchase by Product
            'reports.purchase-by-product-reports.index',
            'reports.purchase-by-product-reports.show',
        ];

        $this->bulkCreateAndAssignPermissions($purchaseReportPermissions, ['admin', 'tech']);

        $this->command->info('✅ Reports permissions seeded successfully!');
    }
}
