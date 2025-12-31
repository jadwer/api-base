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
            // PR-M003: Product Variants
            'variant-attributes.index', 'variant-attributes.show', 'variant-attributes.store', 'variant-attributes.update', 'variant-attributes.destroy',
            'variant-attribute-values.index', 'variant-attribute-values.show', 'variant-attribute-values.store', 'variant-attribute-values.update', 'variant-attribute-values.destroy',
            'product-variants.index', 'product-variants.show', 'product-variants.store', 'product-variants.update', 'product-variants.destroy',
        ];

        $this->bulkCreatePermissions($permissions);
    }
}
