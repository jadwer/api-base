<?php

namespace Modules\Inventory\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\PermissionManager\Models\Permission;
use Modules\PermissionManager\Models\Role;

class InventoryAssignPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $god = Role::where('name', 'god')->first();

        if ($god) {
            $this->command->warn("Asignando permisos de Inventory al rol {$god->name}, sin sobrescribir los existentes...");
            $permissions = Permission::where('name', 'like', 'warehouses.%')
                ->orWhere('name', 'like', 'warehouse-locations.%')
                ->orWhere('name', 'like', 'stocks.%')
                ->orWhere('name', 'like', 'product-batches.%')
                ->orWhere('name', 'like', 'inventory-movements.%')
                ->orWhere('name', 'like', 'inventory.cycle-counts.%')
                ->orWhere('name', 'like', 'product-conversions.%')
                ->orWhere('name', 'like', 'fractionations.%')
                ->get();
            $god->givePermissionTo($permissions);
        }

        $admin = Role::where('name', 'admin')->first();

        if ($admin) {
            $admin->givePermissionTo([
                // Warehouses - Full access (PLURAL)
                'warehouses.index',
                'warehouses.show',
                'warehouses.store',
                'warehouses.update',
                'warehouses.destroy',
                
                // Warehouse Locations - Full access (PLURAL)
                'warehouse-locations.index',
                'warehouse-locations.show',
                'warehouse-locations.store',
                'warehouse-locations.update',
                'warehouse-locations.destroy',
                
                // Stocks - Full access (PLURAL)
                'stocks.index',
                'stocks.show',
                'stocks.store',
                'stocks.update',
                'stocks.destroy',
                
                // Product Batches - Full access (PLURAL)
                'product-batches.index',
                'product-batches.show',
                'product-batches.store',
                'product-batches.update',
                'product-batches.destroy',
                
                // Inventory Movements - Full access (PLURAL)
                'inventory-movements.index',
                'inventory-movements.show',
                'inventory-movements.store',
                'inventory-movements.update',
                'inventory-movements.destroy',

                // Cycle Counts - Full access (IV-M001)
                'inventory.cycle-counts.index',
                'inventory.cycle-counts.show',
                'inventory.cycle-counts.store',
                'inventory.cycle-counts.update',
                'inventory.cycle-counts.destroy',

                // Product Conversions - Full access
                'product-conversions.index',
                'product-conversions.show',
                'product-conversions.store',
                'product-conversions.update',
                'product-conversions.destroy',

                // Fractionations - Full access
                'fractionations.index',
                'fractionations.show',
                'fractionations.store',
                'fractionations.update',
                'fractionations.destroy',
            ]);
        }

        // Tech role - read-only access
        $tech = Role::where('name', 'tech')->first();
        if ($tech) {
            $tech->givePermissionTo([
                // Warehouses - Read only
                'warehouses.index',
                'warehouses.show',
                
                // Warehouse Locations - Read only
                'warehouse-locations.index',
                'warehouse-locations.show',
                
                // Stocks - Read only
                'stocks.index',
                'stocks.show',
                
                // Product Batches - Read only
                'product-batches.index',
                'product-batches.show',
                
                // Inventory Movements - Full access
                'inventory-movements.index',
                'inventory-movements.show',
                'inventory-movements.store',
                'inventory-movements.update',
                'inventory-movements.destroy',

                // Cycle Counts - Read only (IV-M001)
                'inventory.cycle-counts.index',
                'inventory.cycle-counts.show',

                // Product Conversions - Read only
                'product-conversions.index',
                'product-conversions.show',

                // Fractionations - Read only
                'fractionations.index',
                'fractionations.show',
            ]);
        }

        // Customer role - No inventory access
        $customer = Role::where('name', 'customer')->first();
        if ($customer) {
            $customer->givePermissionTo([
            ]);
        }
    }
}
