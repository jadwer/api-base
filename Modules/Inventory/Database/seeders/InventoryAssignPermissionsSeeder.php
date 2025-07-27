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
                ->orWhere('name', 'like', 'stock.%')
                ->orWhere('name', 'like', 'product-batches.%')
                ->get();
            $god->givePermissionTo($permissions);
        }

        $admin = Role::where('name', 'admin')->first();

        if ($admin) {
            $admin->givePermissionTo([
                // Warehouses - Full access (PLURAL)
                'warehouses.index',
                'warehouses.view',
                'warehouses.store',
                'warehouses.update',
                'warehouses.destroy',
                
                // Warehouse Locations - Full access (PLURAL)
                'warehouse-locations.index',
                'warehouse-locations.view',
                'warehouse-locations.store',
                'warehouse-locations.update',
                'warehouse-locations.destroy',
                
                // Stock - Full access
                'stock.index',
                'stock.view',
                'stock.store',
                'stock.update',
                'stock.destroy',
                
                // Product Batches - Full access (PLURAL)
                'product-batches.index',
                'product-batches.view',
                'product-batches.store',
                'product-batches.update',
                'product-batches.destroy',
            ]);
        }

        // Optional: Give limited access to other roles
        $customer = Role::where('name', 'customer')->first();
        if ($customer) {
            $customer->givePermissionTo([
            ]);
        }
    }
}
