<?php

namespace Modules\Product\Database\Factories;

use Modules\Product\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;

class BrandFactory extends Factory
{
    protected $model = Brand::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true) . ' Brand',
            'description' => $this->faker->sentence(8),
        ];
    }

    /**
     * Configure the factory to create a brand with products.
     */
    public function hasProducts(int $count = 1): static
    {
        return $this->has(
            \Modules\Product\Models\Product::factory()->count($count),
            'products'
        );
    }
}
