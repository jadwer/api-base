<?php

namespace Modules\Inventory\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class InventoryPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = [
            // Warehouse permissions (PLURAL para coincidir con JSON:API type)
            ['name' => 'warehouses.index', 'guard_name' => 'api'],
            ['name' => 'warehouses.view', 'guard_name' => 'api'],
            ['name' => 'warehouses.store', 'guard_name' => 'api'],
            ['name' => 'warehouses.update', 'guard_name' => 'api'],
            ['name' => 'warehouses.destroy', 'guard_name' => 'api'],

            // Warehouse Location permissions (PLURAL para coincidir con JSON:API type)
            ['name' => 'warehouse-locations.index', 'guard_name' => 'api'],
            ['name' => 'warehouse-locations.view', 'guard_name' => 'api'],
            ['name' => 'warehouse-locations.store', 'guard_name' => 'api'],
            ['name' => 'warehouse-locations.update', 'guard_name' => 'api'],
            ['name' => 'warehouse-locations.destroy', 'guard_name' => 'api'],

            // Stock permissions (PLURAL para coincidir con JSON:API type y Authorizer)
            ['name' => 'stocks.index', 'guard_name' => 'api'],
            ['name' => 'stocks.view', 'guard_name' => 'api'],
            ['name' => 'stocks.store', 'guard_name' => 'api'],
            ['name' => 'stocks.update', 'guard_name' => 'api'],
            ['name' => 'stocks.destroy', 'guard_name' => 'api'],

            // Product Batch permissions (PLURAL para coincidir con JSON:API type)
            ['name' => 'product-batches.index', 'guard_name' => 'api'],
            ['name' => 'product-batches.view', 'guard_name' => 'api'],
            ['name' => 'product-batches.store', 'guard_name' => 'api'],
            ['name' => 'product-batches.update', 'guard_name' => 'api'],
            ['name' => 'product-batches.destroy', 'guard_name' => 'api'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate($permission);
        }

        $this->command->info('Inventory permissions created successfully.');
    }
}
