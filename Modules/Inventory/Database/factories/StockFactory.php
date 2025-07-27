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
        $quantity = round(rand(100, 10000) / 10, 4); // Genera decimales como 150.2500
        $reservedQuantity = round(rand(0, min(500, $quantity * 10)) / 10, 4);
        $unitCost = round(rand(100, 50000) / 100, 4); // Genera como 125.4567
        
        return [
            'product_id' => \Modules\Product\Models\Product::factory(),
            'warehouse_id' => Warehouse::factory(),
            'warehouse_location_id' => rand(0, 1) ? WarehouseLocation::factory() : null,
            'quantity' => $quantity,
            'reserved_quantity' => $reservedQuantity,
            'minimum_stock' => round(rand(50, 200) / 10, 4),
            'maximum_stock' => rand(0, 1) ? round(rand(5000, 20000) / 10, 4) : null,
            'reorder_point' => round(rand(100, 500) / 10, 4),
            'unit_cost' => $unitCost,
            'status' => ['active', 'inactive', 'quarantine', 'damaged'][array_rand(['active', 'inactive', 'quarantine', 'damaged'])],
            'last_movement_date' => rand(0, 1) ? date('Y-m-d', strtotime('-' . rand(1, 30) . ' days')) : null,
            'last_movement_type' => rand(0, 1) ? ['in', 'out', 'adjustment', 'transfer'][array_rand(['in', 'out', 'adjustment', 'transfer'])] : null,
            'batch_info' => rand(0, 1) ? [
                'batch_number' => 'BAT' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT),
                'expiration_date' => date('Y-m-d', strtotime('+' . rand(30, 730) . ' days'))
            ] : null,
            'metadata' => rand(0, 1) ? [
                'supplier' => 'Proveedor ' . rand(1, 10),
                'quality_grade' => ['A', 'B', 'C'][array_rand(['A', 'B', 'C'])]
            ] : null,
        ];
    }

    /**
     * Indicate that the stock is depleted.
     */
    public function depleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 0.0000,
            'reserved_quantity' => 0.0000,
            'status' => 'inactive',
            'last_movement_type' => 'out',
        ]);
    }

    /**
     * Indicate that the stock is below minimum.
     */
    public function belowMinimum(): static
    {
        return $this->state(function (array $attributes) {
            $minStock = round(rand(200, 500) / 10, 4);
            return [
                'minimum_stock' => $minStock,
                'quantity' => round(rand(10, ($minStock * 10) - 10) / 10, 4),
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
