<?php

namespace Modules\Product\Database\Factories;

use Modules\Product\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true) . ' Category',
            'description' => $this->faker->sentence(10),
        ];
    }

    /**
     * Configure the factory to create a category with products.
     */
    public function hasProducts(int $count = 1): static
    {
        return $this->has(
            \Modules\Product\Models\Product::factory()->count($count),
            'products'
        );
    }
}
