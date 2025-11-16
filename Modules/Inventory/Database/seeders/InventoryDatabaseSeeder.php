<?php

namespace Modules\Inventory\Database\Seeders;

use Illuminate\Database\Seeder;

class InventoryDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            InventoryPermissionSeeder::class,
            InventoryAssignPermissionsSeeder::class,
            // ❌ DEMO DATA - Commented for presentation
            // WarehouseSeeder::class,          // Sample warehouses
            // WarehouseLocationSeeder::class,  // Sample warehouse locations
            // ProductBatchSeeder::class,       // Sample product batches
            // StockSeeder::class,              // Sample stock records
            // InventoryMovementSeeder::class,  // Sample inventory movements
        ]);
    }
}
