<?php

namespace Modules\Purchase\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Purchase\Models\PurchaseOrderItem;
use Modules\Purchase\Models\PurchaseOrder;

class PurchaseOrderItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = PurchaseOrderItem::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 50);
        $unitPrice = $this->faker->randomFloat(2, 10, 1000);
        $subtotal = $quantity * $unitPrice;
        $discount = $this->faker->randomFloat(2, 0, $subtotal * 0.2); // Descuento hasta 20%
        $total = $subtotal - $discount;

        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'product_id' => \Modules\Product\Models\Product::factory(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount' => $discount,
            'subtotal' => $subtotal,
            'total' => $total,
            'metadata' => [
                'notes' => $this->faker->optional(0.3)->sentence(),
                'category' => $this->faker->optional(0.5)->randomElement(['supplies', 'equipment', 'materials']),
                'urgency' => $this->faker->optional(0.4)->randomElement(['low', 'medium', 'high']),
                'tags' => $this->faker->optional(0.3)->words(3),
            ],
        ];
    }

    /**
     * Set a specific product ID.
     */
    public function forProduct(int $productId): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $productId,
        ]);
    }

    /**
     * Set a specific purchase order.
     */
    public function forPurchaseOrder(int $purchaseOrderId): static
    {
        return $this->state(fn (array $attributes) => [
            'purchase_order_id' => $purchaseOrderId,
        ]);
    }
}
