<?php

namespace Modules\Inventory\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inventory\Models\ProductConversion;
use Modules\Product\Models\Product;

class ProductConversionFactory extends Factory
{
    protected $model = ProductConversion::class;

    public function definition(): array
    {
        return [
            'source_product_id' => Product::factory(),
            'destination_product_id' => Product::factory(),
            'conversion_factor' => $this->faker->randomFloat(4, 0.5, 100),
            'waste_percentage' => $this->faker->randomFloat(2, 0, 15),
            'is_active' => true,
            'notes' => $this->faker->optional(0.3)->sentence(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function noWaste(): static
    {
        return $this->state(fn (array $attributes) => [
            'waste_percentage' => 0,
        ]);
    }

    public function forProducts(int $sourceId, int $destinationId): static
    {
        return $this->state(fn (array $attributes) => [
            'source_product_id' => $sourceId,
            'destination_product_id' => $destinationId,
        ]);
    }
}
