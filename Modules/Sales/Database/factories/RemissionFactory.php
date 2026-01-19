<?php

namespace Modules\Sales\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Sales\Models\Remission;
use Modules\Sales\Models\SalesOrder;

/**
 * SA-M006: Remission Factory
 */
class RemissionFactory extends Factory
{
    protected $model = Remission::class;

    public function definition(): array
    {
        return [
            'sales_order_id' => SalesOrder::factory(),
            'shipment_id' => null,
            'warehouse_id' => null,
            'remission_number' => 'REM-' . $this->faker->unique()->numerify('######'),
            'status' => 'draft',
            'remission_date' => now(),
            'delivery_date' => null,
            'delivered_by' => null,
            'received_by' => null,
            'delivery_notes' => null,
            'shipping_address' => null,
            'pdf_path' => null,
            'pdf_generated_at' => null,
            'internal_notes' => null,
            'metadata' => null,
        ];
    }

    /**
     * Indicate that the remission is in printed status.
     */
    public function printed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'printed',
        ]);
    }

    /**
     * Indicate that the remission is delivered.
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'delivered',
            'delivery_date' => now(),
            'received_by' => $this->faker->name(),
        ]);
    }

    /**
     * Indicate that the remission is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    /**
     * Set a specific sales order.
     */
    public function forOrder(SalesOrder $order): static
    {
        return $this->state(fn (array $attributes) => [
            'sales_order_id' => $order->id,
            'shipping_address' => $order->shipping_address,
        ]);
    }
}
