<?php

namespace Modules\Purchase\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\Supplier;

class PurchaseOrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = PurchaseOrder::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'order_date' => $this->faker->date(),
            'status' => $this->faker->randomElement(['pending', 'approved', 'received', 'cancelled']),
            'total_amount' => $this->faker->randomFloat(2, 100, 50000),
            'notes' => $this->faker->optional()->paragraph(),
        ];
    }

    /**
     * Indicate that the purchase order is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate that the purchase order is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
        ]);
    }

    /**
     * Indicate that the purchase order is received.
     */
    public function received(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'received',
        ]);
    }

    /**
     * Indicate that the purchase order is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}
