<?php

namespace Modules\Inventory\Database\Factories;

use Modules\Inventory\Models\Stock;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Models\WarehouseLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockFactory extends Factory
{
    protected $model = Stock::class;

    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(10, 1000);
        $reservedQuantity = $this->faker->numberBetween(0, min(50, $quantity));
        $unitCost = $this->faker->randomFloat(4, 1, 500);
        
        return [
            'product_id' => \Modules\Product\Models\Product::factory(),
            'warehouse_id' => Warehouse::factory(),
            'warehouse_location_id' => $this->faker->boolean(70) ? WarehouseLocation::factory() : null,
            'quantity' => $quantity,
            'reserved_quantity' => $reservedQuantity,
            'minimum_stock' => $this->faker->numberBetween(5, 20),
            'maximum_stock' => $this->faker->optional()->numberBetween(500, 2000),
            'reorder_point' => $this->faker->numberBetween(10, 50),
            'unit_cost' => $unitCost,
            'status' => $this->faker->randomElement(['active', 'inactive', 'blocked', 'depleted']),
            'last_movement_date' => $this->faker->optional()->dateTimeBetween('-30 days', 'now'),
            'last_movement_type' => $this->faker->optional()->randomElement(['in', 'out', 'adjustment', 'transfer']),
            'batch_info' => $this->faker->optional()->randomElement([
                null,
                ['batch_number' => $this->faker->regexify('[A-Z]{3}[0-9]{6}')],
                [
                    'batch_number' => $this->faker->regexify('[A-Z]{3}[0-9]{6}'),
                    'expiration_date' => $this->faker->dateTimeBetween('now', '+2 years')->format('Y-m-d')
                ]
            ]),
            'metadata' => $this->faker->optional()->randomElement([
                null,
                ['supplier' => $this->faker->company],
                ['supplier' => $this->faker->company, 'quality_grade' => 'A'],
            ]),
        ];
    }

    /**
     * Indicate that the stock is depleted.
     */
    public function depleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 0,
            'reserved_quantity' => 0,
            'status' => 'depleted',
            'last_movement_type' => 'out',
        ]);
    }

    /**
     * Indicate that the stock is below minimum.
     */
    public function belowMinimum(): static
    {
        return $this->state(function (array $attributes) {
            $minStock = $this->faker->numberBetween(20, 50);
            return [
                'minimum_stock' => $minStock,
                'quantity' => $this->faker->numberBetween(1, $minStock - 1),
                'status' => 'active',
            ];
        });
    }

    /**
     * Indicate that the stock has high value.
     */
    public function highValue(): static
    {
        return $this->state(fn (array $attributes) => [
            'unit_cost' => $this->faker->randomFloat(4, 100, 1000),
            'quantity' => $this->faker->numberBetween(100, 500),
        ]);
    }
}
