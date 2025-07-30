<?php

namespace Modules\Ecommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\Models\CartItem;

class CartItemFactory extends Factory
{
    protected $model = CartItem::class;

    public function definition(): array
    {
        return [
            'shopping_cart_id' => \Modules\Ecommerce\Models\ShoppingCart::factory(),
            'product_id' => 1, // TODO: Use existing Product ID - \Modules\Product\Models\Product::inRandomOrder()->first()?->id ?? 1,
            'quantity' => $this->faker->numberBetween(1, 10),
            'unit_price' => $this->faker->randomFloat(2, 1, 1000),
            'original_price' => $this->faker->randomFloat(2, 1, 1000),
            'discount_percent' => $this->faker->randomFloat(2, 1, 1000),
            'discount_amount' => $this->faker->randomFloat(2, 1, 1000),
            'subtotal' => $this->faker->randomFloat(2, 1, 1000),
            'tax_rate' => $this->faker->randomFloat(2, 0, 100),
            'tax_amount' => $this->faker->randomFloat(2, 1, 1000),
            'total' => $this->faker->randomFloat(2, 1, 1000),
        ];
    }

}
