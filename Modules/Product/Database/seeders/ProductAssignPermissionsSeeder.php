<?php

namespace Modules\Product\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class ProductAssignPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $god = Role::where('name', 'god')->first();

        if ($god) {
            $this->command->warn("Asignando permisos de Product al rol {$god->name}, sin sobrescribir los existentes...");
            $permissions = Permission::where('name', 'like', 'products.%')
                ->orWhere('name', 'like', 'units.%')
                ->orWhere('name', 'like', 'categories.%')
                ->orWhere('name', 'like', 'brands.%')
                ->get();
            $god->givePermissionTo($permissions);
        }

        $admin = Role::where('name', 'admin')->first();

        if ($admin) {
            $admin->givePermissionTo([
                // Products - Full access
                'products.index',
                'products.view',
                'products.store',
                'products.update',
                'products.destroy',
                
                // Units - Full access
                'units.index',
                'units.view',
                'units.store',
                'units.update',
                'units.destroy',
                
                // Categories - Full access
                'categories.index',
                'categories.view',
                'categories.store',
                'categories.update',
                'categories.destroy',
                
                // Brands - Full access
                'brands.index',
                'brands.view',
                'brands.store',
                'brands.update',
                'brands.destroy',
            ]);
        }

        // Optional: Give limited access to other roles
        $customer = Role::where('name', 'customer')->first();
        if ($customer) {
            $customer->givePermissionTo([
                'products.index',
                'products.view',
                'units.index',
                'units.view',
                'categories.index',
                'categories.view',
                'brands.index',
                'brands.view',
            ]);
        }
    }
}
