<?php

namespace Modules\Product\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class ProductPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Products
            'products.index',
            'products.view',
            'products.store',
            'products.update',
            'products.destroy',
            
            // Units
            'units.index',
            'units.view',
            'units.store',
            'units.update',
            'units.destroy',
            
            // Categories
            'categories.index',
            'categories.view',
            'categories.store',
            'categories.update',
            'categories.destroy',
            
            // Brands
            'brands.index',
            'brands.view',
            'brands.store',
            'brands.update',
            'brands.destroy',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'api',
            ]);
        }
    }
}
