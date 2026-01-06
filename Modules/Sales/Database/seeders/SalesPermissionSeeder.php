<?php

namespace Modules\Sales\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Database\Seeders\Concerns\BulkPermissions;

class SalesPermissionSeeder extends Seeder
{
    use BulkPermissions;

    public function run(): void
    {
        $permissions = [
            'customers.index', 'customers.view', 'customers.show', 'customers.store', 'customers.update', 'customers.destroy',
            'sales-orders.index', 'sales-orders.view', 'sales-orders.show', 'sales-orders.store', 'sales-orders.update', 'sales-orders.destroy',
            'sales-order-items.index', 'sales-order-items.view', 'sales-order-items.show', 'sales-order-items.store', 'sales-order-items.update', 'sales-order-items.destroy',
            // SA-M001: Shipment permissions
            'shipments.index', 'shipments.view', 'shipments.show', 'shipments.store', 'shipments.update', 'shipments.destroy',
            'shipment-items.index', 'shipment-items.view', 'shipment-items.show', 'shipment-items.store', 'shipment-items.update', 'shipment-items.destroy',
            // SA-M002: Backorder permissions
            'backorders.index', 'backorders.view', 'backorders.show', 'backorders.store', 'backorders.update', 'backorders.destroy',
            // SA-M003: Discount Rule permissions
            'discount-rules.index', 'discount-rules.view', 'discount-rules.show', 'discount-rules.store', 'discount-rules.update', 'discount-rules.destroy',
        ];

        $this->bulkCreatePermissions($permissions);
        Log::info('Sales permissions created successfully.');
    }
}
