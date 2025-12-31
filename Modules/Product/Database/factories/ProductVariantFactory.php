<?php

namespace Modules\Product\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductVariant;

/**
 * PR-M003: Factory for ProductVariant model.
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        $product = Product::first() ?? Product::factory()->create();

        return [
            'product_id' => $product->id,
            'sku' => strtoupper($this->faker->unique()->bothify('VAR-????-####')),
            'name' => null, // Will use auto-generated from attributes
            'price' => $this->faker->optional(0.7)->randomFloat(2, 10, 1000),
            'cost' => $this->faker->optional(0.5)->randomFloat(2, 5, 500),
            'weight' => $this->faker->optional(0.6)->randomFloat(2, 0.1, 50),
            'barcode' => $this->faker->optional(0.4)->ean13(),
            'img_path' => null,
            'stock_quantity' => $this->faker->numberBetween(0, 500),
            'low_stock_threshold' => $this->faker->optional(0.5)->numberBetween(5, 50),
            'is_active' => $this->faker->boolean(90),
            'is_default' => false,
            'metadata' => $this->faker->optional(0.3)->passthrough([
                'origin' => $this->faker->country(),
                'batch' => $this->faker->bothify('BATCH-####'),
            ]),
        ];
    }

    /**
     * For specific product.
     */
    public function forProduct(Product $product): static
    {
        return $this->state(fn() => [
            'product_id' => $product->id,
        ]);
    }

    /**
     * Default variant.
     */
    public function default(): static
    {
        return $this->state(fn() => [
            'is_default' => true,
        ]);
    }

    /**
     * Active variant.
     */
    public function active(): static
    {
        return $this->state(fn() => [
            'is_active' => true,
        ]);
    }

    /**
     * Inactive variant.
     */
    public function inactive(): static
    {
        return $this->state(fn() => [
            'is_active' => false,
        ]);
    }

    /**
     * With custom price.
     */
    public function withPrice(float $price): static
    {
        return $this->state(fn() => [
            'price' => $price,
        ]);
    }

    /**
     * In stock variant.
     */
    public function inStock(int $quantity = 100): static
    {
        return $this->state(fn() => [
            'stock_quantity' => $quantity,
        ]);
    }

    /**
     * Out of stock variant.
     */
    public function outOfStock(): static
    {
        return $this->state(fn() => [
            'stock_quantity' => 0,
        ]);
    }

    /**
     * Low stock variant.
     */
    public function lowStock(): static
    {
        return $this->state(fn() => [
            'stock_quantity' => 5,
            'low_stock_threshold' => 10,
        ]);
    }

    /**
     * With barcode.
     */
    public function withBarcode(): static
    {
        return $this->state(fn() => [
            'barcode' => $this->faker->ean13(),
        ]);
    }
}
