<?php

namespace Modules\Sales\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Product\Models\Product;
use Modules\Sales\Models\Backorder;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;

/**
 * SA-M002: Factory for Backorder model.
 */
class BackorderFactory extends Factory
{
    protected $model = Backorder::class;

    public function definition(): array
    {
        $originalQty = $this->faker->randomFloat(2, 10, 100);
        $backorderQty = $this->faker->randomFloat(2, 1, $originalQty);
        $fulfilledQty = $this->faker->randomFloat(2, 0, $backorderQty * 0.5);

        $status = 'pending';
        if ($fulfilledQty >= $backorderQty) {
            $status = 'fulfilled';
        } elseif ($fulfilledQty > 0) {
            $status = 'partially_fulfilled';
        }

        return [
            'sales_order_id' => SalesOrder::factory()->state(['status' => 'confirmed']),
            'sales_order_item_id' => SalesOrderItem::factory(),
            'product_id' => Product::factory(),
            'warehouse_id' => null,
            'backorder_number' => Backorder::generateBackorderNumber(),
            'original_quantity' => $originalQty,
            'backorder_quantity' => $backorderQty,
            'fulfilled_quantity' => $fulfilledQty,
            'status' => $status,
            'priority' => $this->faker->randomElement(['low', 'normal', 'high', 'urgent']),
            'expected_date' => $this->faker->optional(0.6)->dateTimeBetween('+1 week', '+2 months')?->format('Y-m-d'),
            'promised_date' => $this->faker->optional(0.4)->dateTimeBetween('+1 week', '+3 months')?->format('Y-m-d'),
            'notes' => $this->faker->optional(0.3)->sentence(),
            'customer_notes' => $this->faker->optional(0.2)->sentence(),
            'metadata' => null,
        ];
    }

    /**
     * Backorder in pending status.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'fulfilled_quantity' => 0,
        ]);
    }

    /**
     * Backorder partially fulfilled.
     */
    public function partiallyFulfilled(): static
    {
        return $this->state(function (array $attributes) {
            $backorderQty = $attributes['backorder_quantity'] ?? 10;
            return [
                'status' => 'partially_fulfilled',
                'fulfilled_quantity' => $backorderQty * 0.5,
            ];
        });
    }

    /**
     * Backorder fully fulfilled.
     */
    public function fulfilled(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'fulfilled',
                'fulfilled_quantity' => $attributes['backorder_quantity'] ?? 10,
            ];
        });
    }

    /**
     * Backorder cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'metadata' => [
                'cancelled_at' => now()->toIso8601String(),
                'cancellation_reason' => $this->faker->randomElement([
                    'Customer request',
                    'Product discontinued',
                    'Order cancelled',
                ]),
            ],
        ]);
    }

    /**
     * High priority backorder.
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'high',
        ]);
    }

    /**
     * Urgent priority backorder.
     */
    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'urgent',
        ]);
    }

    /**
     * Overdue backorder.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'fulfilled_quantity' => 0,
            'expected_date' => $this->faker->dateTimeBetween('-2 weeks', '-1 day')->format('Y-m-d'),
        ]);
    }

    /**
     * With promised date.
     */
    public function withPromisedDate(): static
    {
        return $this->state(fn (array $attributes) => [
            'promised_date' => $this->faker->dateTimeBetween('+1 week', '+1 month')->format('Y-m-d'),
            'customer_notes' => 'Delivery promised by this date.',
        ]);
    }
}
