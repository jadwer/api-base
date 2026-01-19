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
            // SA-M004: Quote permissions
            'quotes.index', 'quotes.view', 'quotes.show', 'quotes.store', 'quotes.update', 'quotes.destroy',
            'quote-items.index', 'quote-items.view', 'quote-items.show', 'quote-items.store', 'quote-items.update', 'quote-items.destroy',
            // SA-M005: Folio Sequence permissions (admin only)
            'sales.folio-sequences.index', 'sales.folio-sequences.show', 'sales.folio-sequences.update',
            // SA-M006: Remission permissions
            'remissions.index', 'remissions.view', 'remissions.show', 'remissions.store', 'remissions.update', 'remissions.destroy',
            'remission-items.index', 'remission-items.view', 'remission-items.show', 'remission-items.store', 'remission-items.update', 'remission-items.destroy',
        ];

        $this->bulkCreatePermissions($permissions);
        Log::info('Sales permissions created successfully.');
    }
}
