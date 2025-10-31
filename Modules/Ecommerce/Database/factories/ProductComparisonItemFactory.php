<?php

namespace Modules\Ecommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\Models\ProductComparison;
use Modules\Ecommerce\Models\ProductComparisonItem;
use Modules\Product\Models\Product;

class ProductComparisonItemFactory extends Factory
{
    protected $model = ProductComparisonItem::class;

    public function definition(): array
    {
        return [
            'comparison_id' => ProductComparison::factory(),
            'product_id' => Product::factory(),
            'position' => $this->faker->numberBetween(0, 10),
        ];
    }
}
