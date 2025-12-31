<?php

namespace Modules\Sales\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Sales\Models\SalesOrderItem;
use Modules\Sales\Models\Shipment;
use Modules\Sales\Models\ShipmentItem;

/**
 * SA-M001: Factory for ShipmentItem model.
 */
class ShipmentItemFactory extends Factory
{
    protected $model = ShipmentItem::class;

    public function definition(): array
    {
        return [
            'shipment_id' => Shipment::factory(),
            'sales_order_item_id' => SalesOrderItem::factory(),
            'quantity' => $this->faker->randomFloat(2, 1, 20),
            'notes' => $this->faker->optional(0.3)->sentence(),
            'metadata' => $this->faker->optional(0.3)->passthrough([
                'picked_by' => $this->faker->name(),
                'picked_at' => $this->faker->dateTimeBetween('-1 week', 'now')->format('Y-m-d H:i:s'),
                'location' => $this->faker->randomElement(['A-01-02', 'B-03-01', 'C-05-04']),
            ]),
        ];
    }

    /**
     * Full quantity shipment (ships all remaining).
     */
    public function fullQuantity(): static
    {
        return $this->state(function (array $attributes) {
            // This will be set properly when using with a real order item
            return [
                'quantity' => $attributes['quantity'] ?? $this->faker->randomFloat(2, 5, 50),
            ];
        });
    }

    /**
     * Partial quantity shipment.
     */
    public function partialQuantity(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $this->faker->randomFloat(2, 1, 5),
        ]);
    }

    /**
     * Single item quantity.
     */
    public function singleItem(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 1,
        ]);
    }

    /**
     * With detailed picking info.
     */
    public function withPickingInfo(): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => [
                'picked_by' => $this->faker->name(),
                'picked_at' => $this->faker->dateTimeBetween('-1 week', 'now')->format('Y-m-d H:i:s'),
                'location' => $this->faker->randomElement(['A-01-02', 'B-03-01', 'C-05-04']),
                'batch_number' => $this->faker->regexify('BAT[0-9]{6}'),
                'serial_numbers' => array_map(
                    fn() => $this->faker->regexify('SN[A-Z0-9]{8}'),
                    range(1, min(3, (int)($attributes['quantity'] ?? 1)))
                ),
            ],
        ]);
    }
}
