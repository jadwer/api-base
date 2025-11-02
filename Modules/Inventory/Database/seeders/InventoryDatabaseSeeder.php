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
            // WarehouseSeeder::class, // Commented for performance - creates test data
            // WarehouseLocationSeeder::class, // Commented for performance - creates test data
            // StockSeeder::class, // Commented for performance - creates test data
            // ProductBatchSeeder::class, // Commented for performance - creates test data
            // InventoryMovementSeeder::class, // Commented for performance - creates test data
        ]);
    }
}
