<?php

namespace Modules\Product\Database\Factories;

use Modules\Product\Models\Product;
use Modules\Product\Models\Unit;
use Modules\Product\Models\Category;
use Modules\Product\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true) . ' ' . $this->faker->randomElement([
                'Pro', 'Premium', 'Deluxe', 'Standard', 'Classic', 'Advanced'
            ]),
            'sku' => strtoupper($this->faker->unique()->bothify('???-###')),
            'description' => $this->faker->sentence(8),
            'full_description' => $this->faker->paragraph(4),
            'price' => $this->faker->randomFloat(2, 10, 999),
            'cost' => $this->faker->randomFloat(2, 5, 500),
            'iva' => $this->faker->boolean(70), // 70% probability of having IVA
            'img_path' => '/images/products/' . $this->faker->uuid . '.jpg',
            'datasheet_path' => '/docs/products/' . $this->faker->uuid . '.pdf',
            'unit_id' => Unit::factory(),
            'category_id' => Category::factory(),
            'brand_id' => Brand::factory(),
        ];
    }

    /**
     * Product without IVA
     */
    public function withoutIva(): static
    {
        return $this->state(fn (array $attributes) => [
            'iva' => false,
        ]);
    }

    /**
     * Expensive product
     */
    public function expensive(): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => $this->faker->randomFloat(2, 1000, 5000),
            'cost' => $this->faker->randomFloat(2, 500, 2500),
        ]);
    }

    /**
     * Product with specific category
     */
    public function inCategory(Category $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => $category->id,
        ]);
    }
}
