<?php

namespace Modules\Inventory\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inventory\Models\CycleCount;
use Modules\Inventory\Models\Warehouse;
use Modules\Product\Models\Product;
use App\Models\User;

class CycleCountFactory extends Factory
{
    protected $model = CycleCount::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement(['scheduled', 'in_progress', 'completed']);
        $systemQty = $this->faker->randomFloat(2, 10, 1000);
        $variance = $this->faker->randomFloat(2, -50, 50);
        $countedQty = $systemQty + $variance;

        return [
            'count_number' => 'CC-' . str_pad($this->faker->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'warehouse_id' => Warehouse::factory(),
            'warehouse_location_id' => null,
            'product_id' => Product::factory(),
            'scheduled_date' => $this->faker->dateTimeBetween('-1 month', '+1 month'),
            'completed_date' => $status === 'completed' ? $this->faker->dateTimeBetween('-1 week', 'now') : null,
            'status' => $status,
            'system_quantity' => $status === 'completed' ? $systemQty : null,
            'counted_quantity' => $status === 'completed' ? $countedQty : null,
            'variance_quantity' => $status === 'completed' ? $variance : null,
            'variance_value' => $status === 'completed' ? $variance * $this->faker->randomFloat(2, 10, 100) : null,
            'assigned_to' => User::factory(),
            'counted_by' => $status === 'completed' ? User::factory() : null,
            'abc_class' => $this->faker->randomElement(['A', 'B', 'C']),
            'adjustment_movement_id' => null,
            'notes' => $this->faker->optional()->sentence(),
            'metadata' => null,
        ];
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
            'completed_date' => null,
            'system_quantity' => null,
            'counted_quantity' => null,
            'variance_quantity' => null,
            'variance_value' => null,
            'counted_by' => null,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'completed_date' => null,
            'system_quantity' => null,
            'counted_quantity' => null,
            'variance_quantity' => null,
            'variance_value' => null,
        ]);
    }

    public function completed(): static
    {
        $systemQty = $this->faker->randomFloat(2, 10, 1000);
        $variance = $this->faker->randomFloat(2, -50, 50);

        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'completed_date' => now(),
            'system_quantity' => $systemQty,
            'counted_quantity' => $systemQty + $variance,
            'variance_quantity' => $variance,
            'variance_value' => $variance * 50,
            'counted_by' => User::factory(),
        ]);
    }

    public function withVariance(float $variance = 10): static
    {
        $systemQty = $this->faker->randomFloat(2, 100, 500);

        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'completed_date' => now(),
            'system_quantity' => $systemQty,
            'counted_quantity' => $systemQty + $variance,
            'variance_quantity' => $variance,
            'variance_value' => $variance * 50,
        ]);
    }

    public function noVariance(): static
    {
        $systemQty = $this->faker->randomFloat(2, 100, 500);

        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'completed_date' => now(),
            'system_quantity' => $systemQty,
            'counted_quantity' => $systemQty,
            'variance_quantity' => 0,
            'variance_value' => 0,
        ]);
    }

    public function classA(): static
    {
        return $this->state(fn (array $attributes) => [
            'abc_class' => 'A',
        ]);
    }

    public function classB(): static
    {
        return $this->state(fn (array $attributes) => [
            'abc_class' => 'B',
        ]);
    }

    public function classC(): static
    {
        return $this->state(fn (array $attributes) => [
            'abc_class' => 'C',
        ]);
    }
}
