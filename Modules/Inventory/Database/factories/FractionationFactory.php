<?php

namespace Modules\Inventory\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inventory\Models\Fractionation;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\ProductConversion;
use Modules\Inventory\Models\Warehouse;
use Modules\Product\Models\Product;
use Modules\User\Models\User;

class FractionationFactory extends Factory
{
    protected $model = Fractionation::class;

    public function definition(): array
    {
        $sourceQuantity = $this->faker->randomFloat(4, 1, 500);
        $conversionFactor = $this->faker->randomFloat(4, 0.5, 100);
        $wastePercentage = $this->faker->randomFloat(2, 0, 15);
        $producedQuantity = round($sourceQuantity * $conversionFactor * (1 - $wastePercentage / 100), 4);
        $wasteQuantity = round($sourceQuantity * $conversionFactor * ($wastePercentage / 100), 4);

        return [
            'folio_number' => 'FRAC-' . date('y') . '-' . str_pad($this->faker->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'source_product_id' => Product::factory(),
            'destination_product_id' => Product::factory(),
            'product_conversion_id' => null,
            'warehouse_id' => Warehouse::factory(),
            'user_id' => User::factory(),
            'source_quantity' => $sourceQuantity,
            'produced_quantity' => $producedQuantity,
            'waste_percentage' => $wastePercentage,
            'waste_quantity' => $wasteQuantity,
            'conversion_factor_used' => $conversionFactor,
            'exit_movement_id' => null,
            'entry_movement_id' => null,
            'status' => Fractionation::STATUS_COMPLETED,
            'notes' => $this->faker->optional(0.3)->sentence(),
            'executed_at' => now(),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Fractionation::STATUS_COMPLETED,
            'executed_at' => now(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Fractionation::STATUS_PENDING,
            'executed_at' => null,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Fractionation::STATUS_CANCELLED,
        ]);
    }

    public function forWarehouse(int $warehouseId): static
    {
        return $this->state(fn (array $attributes) => [
            'warehouse_id' => $warehouseId,
        ]);
    }

    public function byUser(int $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
        ]);
    }
}
