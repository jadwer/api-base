<?php

namespace Modules\Ecommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\Models\CartItem;

class CartItemFactory extends Factory
{
    protected $model = CartItem::class;

    /**
     * El descuento por omision es aleatorio (0-100) sin relacion con el
     * precio; al pasar a pedido el modelo rechaza descuentos mayores al
     * importe (22-10). Se topa al 20% del importe de la linea.
     */
    public function configure(): static
    {
        return $this->afterMaking(function ($item) {
            $gross = round((float) $item->quantity * (float) $item->unit_price, 2);
            if ((float) $item->discount_amount > $gross) {
                $item->discount_amount = round($gross * 0.2, 2);
            }
        });
    }

    public function definition(): array
    {
        // Ensure we have at least one product
        $productId = \Modules\Product\Models\Product::inRandomOrder()->first()?->id ?? 
                    \Modules\Product\Models\Product::factory()->create()->id;

        return [
            'shopping_cart_id' => \Modules\Ecommerce\Models\ShoppingCart::factory(),
            'product_id' => $productId,
            'quantity' => $this->faker->numberBetween(1, 10),
            'unit_price' => $this->faker->randomFloat(2, 10, 500),
            'original_price' => $this->faker->randomFloat(2, 10, 500),
            'discount_percent' => $this->faker->randomFloat(2, 0, 50),
            'discount_amount' => $this->faker->randomFloat(2, 0, 100),
            'subtotal' => $this->faker->randomFloat(2, 10, 400),
            'tax_rate' => $this->faker->randomFloat(2, 0, 25),
            'tax_amount' => $this->faker->randomFloat(2, 0, 100),
            'total' => $this->faker->randomFloat(2, 10, 500),
            'metadata' => [],
            'status' => 'active',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 0,
            'status' => 'inactive',
        ]);
    }

}
