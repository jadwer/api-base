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

        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'product_id' => 1, // Usar ID fijo por ahora hasta que Products esté configurado
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $subtotal,
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
