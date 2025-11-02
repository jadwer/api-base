<?php

namespace Modules\Product\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Database\Seeders\Concerns\BulkPermissions;

class ProductPermissionSeeder extends Seeder
{
    use BulkPermissions;

    public function run(): void
    {
        $permissions = [
            'products.index', 'products.show', 'products.store', 'products.update', 'products.destroy',
            'units.index', 'units.show', 'units.store', 'units.update', 'units.destroy',
            'categories.index', 'categories.show', 'categories.store', 'categories.update', 'categories.destroy',
            'brands.index', 'brands.show', 'brands.store', 'brands.update', 'brands.destroy',
        ];

        $this->bulkCreatePermissions($permissions);
    }
}
