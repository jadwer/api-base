<?php

namespace Modules\Ecommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\Models\ShoppingCart;

class ShoppingCartFactory extends Factory
{
    protected $model = ShoppingCart::class;

    public function definition(): array
    {
        return [
            'session_id' => $this->faker->optional(0.3)->regexify('sess_[a-f0-9]{32}'),
            'user_id' => 1, // TODO: Use existing User ID - \Modules\User\Models\User::inRandomOrder()->first()?->id ?? 1,
            'status' => $this->faker->randomElement(['active', 'inactive', 'expired']),
            'expires_at' => $this->faker->dateTimeBetween('+1 day', '+90 days'),
            'total_amount' => $this->faker->randomFloat(2, 1, 1000),
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'MXN']),
            'coupon_code' => $this->faker->optional(0.3)->lexify('????##'),
            'discount_amount' => $this->faker->randomFloat(2, 0, 100),
            'tax_amount' => $this->faker->randomFloat(2, 0, 50),
            'shipping_amount' => $this->faker->randomFloat(2, 0, 25),
            'notes' => $this->faker->optional(0.7)->paragraph(),
        ];
    }

    /**
     * Active ShoppingCart
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Inactive ShoppingCart
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
