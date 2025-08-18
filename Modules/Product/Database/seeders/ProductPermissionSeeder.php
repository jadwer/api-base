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
            'products.show',
            'products.store',
            'products.update',
            'products.destroy',
            
            // Units
            'units.index',
            'units.show',
            'units.store',
            'units.update',
            'units.destroy',
            
            // Categories
            'categories.index',
            'categories.show',
            'categories.store',
            'categories.update',
            'categories.destroy',
            
            // Brands
            'brands.index',
            'brands.show',
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
