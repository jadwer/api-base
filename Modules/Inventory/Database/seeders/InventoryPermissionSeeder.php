<?php

namespace Modules\Inventory\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Database\Seeders\Concerns\BulkPermissions;

class InventoryPermissionSeeder extends Seeder
{
    use BulkPermissions;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = [
            // Warehouse permissions (PLURAL para coincidir con JSON:API type)
            'warehouses.index',
            'warehouses.show',
            'warehouses.store',
            'warehouses.update',
            'warehouses.destroy',

            // Warehouse Location permissions (PLURAL para coincidir con JSON:API type)
            'warehouse-locations.index',
            'warehouse-locations.show',
            'warehouse-locations.store',
            'warehouse-locations.update',
            'warehouse-locations.destroy',

            // Stock permissions (PLURAL para coincidir con JSON:API type y Authorizer)
            'stocks.index',
            'stocks.show',
            'stocks.store',
            'stocks.update',
            'stocks.destroy',

            // Product Batch permissions (PLURAL para coincidir con JSON:API type)
            'product-batches.index',
            'product-batches.show',
            'product-batches.store',
            'product-batches.update',
            'product-batches.destroy',

            // Inventory Movement permissions (PLURAL para coincidir con JSON:API type)
            'inventory-movements.index',
            'inventory-movements.show',
            'inventory-movements.store',
            'inventory-movements.update',
            'inventory-movements.destroy',

            // Cycle Count permissions (IV-M001)
            'inventory.cycle-counts.index',
            'inventory.cycle-counts.show',
            'inventory.cycle-counts.store',
            'inventory.cycle-counts.update',
            'inventory.cycle-counts.destroy',
        ];

        $this->bulkCreatePermissions($permissions);

        $this->command->info('Inventory permissions created successfully.');
    }
}
