<?php

namespace Modules\Purchase\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Database\Seeders\Concerns\BulkPermissions;

class PurchasePermissionSeeder extends Seeder
{
    use BulkPermissions;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Supplier permissions
            'suppliers.index',
            'suppliers.show',
            'suppliers.store',
            'suppliers.update',
            'suppliers.destroy',

            // Purchase Order permissions
            'purchase-orders.index',
            'purchase-orders.show',
            'purchase-orders.store',
            'purchase-orders.update',
            'purchase-orders.destroy',

            // Purchase Order Item permissions
            'purchase-order-items.index',
            'purchase-order-items.show',
            'purchase-order-items.store',
            'purchase-order-items.update',
            'purchase-order-items.destroy',

            // Purchase Order Approval permissions (PU-001)
            'purchase.approve-tier1',        // Procurement Manager: >50K
            'purchase.approve-tier2',        // Finance Director: >250K
            'purchase.approve-tier3',        // CFO: >1M
            'purchase.approve-new-supplier', // Approve first-time suppliers
        ];

        $this->bulkCreatePermissions($permissions);

        Log::info('Purchase permissions created successfully.');
    }
}
