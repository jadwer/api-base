<?php

namespace Modules\Sales\Database\Factories;

use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalesOrderFactory extends Factory
{
    protected $model = SalesOrder::class;

    public function definition(): array
    {
        $orderDate = $this->faker->dateTimeBetween('-6 months', 'now');
        $status = $this->faker->randomElement(['draft', 'pending', 'approved', 'delivered', 'cancelled']);
        
        // Calcular fechas según el estado
        $approvedAt = null;
        $deliveredAt = null;
        
        if (in_array($status, ['approved', 'delivered'])) {
            $approvedAt = $this->faker->dateTimeBetween($orderDate, 'now');
        }
        
        if ($status === 'delivered') {
            $deliveredAt = $this->faker->dateTimeBetween($approvedAt ?: $orderDate, 'now');
        }
        
        $totalAmount = $this->faker->randomFloat(2, 100, 50000);
        $discountTotal = $this->faker->randomFloat(2, 0, $totalAmount * 0.2);
        
        return [
            'customer_id' => Customer::factory(),
            'order_number' => 'SO-' . strtoupper($this->faker->unique()->regexify('[A-Z0-9]{8}')),
            'status' => $status,
            'order_date' => $orderDate->format('Y-m-d'),
            'approved_at' => $approvedAt?->format('Y-m-d H:i:s'),
            'delivered_at' => $deliveredAt?->format('Y-m-d H:i:s'),
            'total_amount' => $totalAmount,
            'discount_total' => $discountTotal,
            'notes' => $this->faker->optional(0.4)->paragraph(),
            'metadata' => $this->faker->optional(0.5)->passthrough([
                'sales_rep' => $this->faker->name(),
                'payment_terms' => $this->faker->randomElement(['cash', '30_days', '60_days', '90_days']),
                'shipping_method' => $this->faker->randomElement(['pickup', 'delivery', 'courier']),
                'priority' => $this->faker->randomElement(['low', 'normal', 'high', 'urgent']),
                'source' => $this->faker->randomElement(['web', 'phone', 'email', 'in_person'])
            ]),
        ];
    }

    /**
     * Indicate that the order is in draft status.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'approved_at' => null,
            'delivered_at' => null,
        ]);
    }

    /**
     * Indicate that the order is pending approval.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'approved_at' => null,
            'delivered_at' => null,
        ]);
    }

    /**
     * Indicate that the order is approved.
     */
    public function approved(): static
    {
        return $this->state(function (array $attributes) {
            $orderDate = $attributes['order_date'] ?? now()->format('Y-m-d');
            $approvedAt = $this->faker->dateTimeBetween($orderDate, 'now');
            
            return [
                'status' => 'approved',
                'approved_at' => $approvedAt->format('Y-m-d H:i:s'),
                'delivered_at' => null,
            ];
        });
    }

    /**
     * Indicate that the order is delivered.
     */
    public function delivered(): static
    {
        return $this->state(function (array $attributes) {
            $orderDate = $attributes['order_date'] ?? now()->format('Y-m-d');
            $approvedAt = $this->faker->dateTimeBetween($orderDate, 'now');
            $deliveredAt = $this->faker->dateTimeBetween($approvedAt, 'now');
            
            return [
                'status' => 'delivered',
                'approved_at' => $approvedAt->format('Y-m-d H:i:s'),
                'delivered_at' => $deliveredAt->format('Y-m-d H:i:s'),
            ];
        });
    }

    /**
     * Indicate that the order is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'approved_at' => null,
            'delivered_at' => null,
            'notes' => 'Order cancelled: ' . $this->faker->sentence(),
        ]);
    }

    /**
     * Indicate that the order has high value.
     */
    public function highValue(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_amount' => $this->faker->randomFloat(2, 20000, 100000),
            'discount_total' => $this->faker->randomFloat(2, 1000, 5000),
        ]);
    }

    /**
     * Indicate that the order has low value.
     */
    public function lowValue(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_amount' => $this->faker->randomFloat(2, 50, 500),
            'discount_total' => $this->faker->randomFloat(2, 0, 50),
        ]);
    }

    /**
     * Indicate that the order has urgent priority.
     */
    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'priority' => 'urgent',
                'rush_delivery' => true,
                'special_instructions' => 'URGENT - Process immediately'
            ]),
        ]);
    }

    /**
     * Indicate that the order is recent.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'order_date' => $this->faker->dateTimeBetween('-1 week', 'now')->format('Y-m-d'),
        ]);
    }

    /**
     * Indicate that the order is old.
     */
    public function old(): static
    {
        return $this->state(fn (array $attributes) => [
            'order_date' => $this->faker->dateTimeBetween('-2 years', '-6 months')->format('Y-m-d'),
        ]);
    }
}
