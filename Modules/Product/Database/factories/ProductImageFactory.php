<?php

namespace Modules\Product\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductImage;

class ProductImageFactory extends Factory
{
    protected $model = ProductImage::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'file_path' => 'products/' . Str::uuid() . '.webp',
            'alt_text' => $this->faker->sentence(3),
            'sort_order' => 0,
            'is_primary' => false,
        ];
    }

    /**
     * Mark this image as the primary image.
     */
    public function primary(): static
    {
        return $this->state(fn() => [
            'is_primary' => true,
        ]);
    }

    /**
     * Set a specific sort order.
     */
    public function withOrder(int $order): static
    {
        return $this->state(fn() => [
            'sort_order' => $order,
        ]);
    }

    /**
     * Assign to a specific product.
     */
    public function forProduct(Product $product): static
    {
        return $this->state(fn() => [
            'product_id' => $product->id,
        ]);
    }
}
