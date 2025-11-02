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
        ];

        $this->bulkCreatePermissions($permissions);
        Log::info('Sales permissions created successfully.');
    }
}
