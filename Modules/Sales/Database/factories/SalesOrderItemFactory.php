<?php

namespace Modules\Sales\Database\Factories;

use Modules\Sales\Models\SalesOrderItem;
use Modules\Sales\Models\SalesOrder;
use Modules\Product\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalesOrderItemFactory extends Factory
{
    protected $model = SalesOrderItem::class;

    public function definition(): array
    {
        $quantity = $this->faker->randomFloat(4, 1, 100);
        $unitPrice = $this->faker->randomFloat(2, 10, 1000);
        $discount = $this->faker->randomFloat(2, 0, $unitPrice * $quantity * 0.2);
        $total = ($unitPrice * $quantity) - $discount;
        
        return [
            'sales_order_id' => SalesOrder::factory(),
            'product_id' => Product::factory(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount' => $discount,
            'total' => $total,
            'metadata' => $this->faker->optional(0.4)->passthrough([
                'notes' => $this->faker->optional(0.5)->sentence(),
                'special_instructions' => $this->faker->optional(0.3)->sentence(),
                'warehouse_location' => $this->faker->optional(0.6)->randomElement(['A1', 'B2', 'C3', 'D4']),
                'batch_number' => $this->faker->optional(0.4)->regexify('BAT[0-9]{6}'),
                'delivery_date' => $this->faker->optional(0.3)->passthrough(
                    $this->faker->dateTimeBetween('now', '+1 month')?->format('Y-m-d')
                ),
            ]),
        ];
    }

    /**
     * Indicate that the item has high quantity.
     */
    public function highQuantity(): static
    {
        return $this->state(function (array $attributes) {
            $quantity = $this->faker->randomFloat(4, 50, 500);
            $unitPrice = $attributes['unit_price'] ?? $this->faker->randomFloat(2, 10, 1000);
            $discount = $attributes['discount'] ?? 0;
            
            return [
                'quantity' => $quantity,
                'total' => ($unitPrice * $quantity) - $discount,
            ];
        });
    }

    /**
     * Indicate that the item has low quantity.
     */
    public function lowQuantity(): static
    {
        return $this->state(function (array $attributes) {
            $quantity = $this->faker->randomFloat(4, 0.1, 5);
            $unitPrice = $attributes['unit_price'] ?? $this->faker->randomFloat(2, 10, 1000);
            $discount = $attributes['discount'] ?? 0;
            
            return [
                'quantity' => $quantity,
                'total' => ($unitPrice * $quantity) - $discount,
            ];
        });
    }

    /**
     * Indicate that the item has high unit price.
     */
    public function highPrice(): static
    {
        return $this->state(function (array $attributes) {
            $unitPrice = $this->faker->randomFloat(2, 500, 5000);
            $quantity = $attributes['quantity'] ?? $this->faker->randomFloat(4, 1, 100);
            $discount = $attributes['discount'] ?? 0;
            
            return [
                'unit_price' => $unitPrice,
                'total' => ($unitPrice * $quantity) - $discount,
            ];
        });
    }

    /**
     * Indicate that the item has low unit price.
     */
    public function lowPrice(): static
    {
        return $this->state(function (array $attributes) {
            $unitPrice = $this->faker->randomFloat(2, 1, 20);
            $quantity = $attributes['quantity'] ?? $this->faker->randomFloat(4, 1, 100);
            $discount = $attributes['discount'] ?? 0;
            
            return [
                'unit_price' => $unitPrice,
                'total' => ($unitPrice * $quantity) - $discount,
            ];
        });
    }

    /**
     * Indicate that the item has a discount.
     */
    public function withDiscount(): static
    {
        return $this->state(function (array $attributes) {
            $quantity = $attributes['quantity'] ?? $this->faker->randomFloat(4, 1, 100);
            $unitPrice = $attributes['unit_price'] ?? $this->faker->randomFloat(2, 10, 1000);
            $subtotal = $unitPrice * $quantity;
            $discount = $this->faker->randomFloat(2, $subtotal * 0.05, $subtotal * 0.25);
            
            return [
                'discount' => $discount,
                'total' => $subtotal - $discount,
            ];
        });
    }

    /**
     * Indicate that the item has no discount.
     */
    public function noDiscount(): static
    {
        return $this->state(function (array $attributes) {
            $quantity = $attributes['quantity'] ?? $this->faker->randomFloat(4, 1, 100);
            $unitPrice = $attributes['unit_price'] ?? $this->faker->randomFloat(2, 10, 1000);
            
            return [
                'discount' => 0.00,
                'total' => $unitPrice * $quantity,
            ];
        });
    }

    /**
     * Indicate that the item has batch tracking.
     */
    public function withBatch(): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'batch_number' => 'BAT' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT),
                'expiration_date' => $this->faker->dateTimeBetween('+1 month', '+2 years')->format('Y-m-d'),
                'lot_number' => 'LOT' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
            ]),
        ]);
    }

    /**
     * Indicate that the item has special instructions.
     */
    public function withSpecialInstructions(): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'special_instructions' => $this->faker->randomElement([
                    'Handle with care - fragile',
                    'Keep refrigerated',
                    'Deliver first',
                    'Check quality before delivery',
                    'Customer pickup only'
                ]),
                'priority' => $this->faker->randomElement(['normal', 'high', 'urgent']),
            ]),
        ]);
    }

    /**
     * Recalculate total based on quantity, unit price and discount.
     */
    public function recalculateTotal(): static
    {
        return $this->state(function (array $attributes) {
            $quantity = $attributes['quantity'] ?? 1;
            $unitPrice = $attributes['unit_price'] ?? 0;
            $discount = $attributes['discount'] ?? 0;
            
            return [
                'total' => ($unitPrice * $quantity) - $discount,
            ];
        });
    }
}
