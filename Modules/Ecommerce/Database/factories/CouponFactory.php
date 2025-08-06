<?php

namespace Modules\Ecommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\Models\Coupon;

class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->lexify('????##'),
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->optional(0.7)->paragraph(),
            'type' => $this->faker->randomElement(['percentage', 'fixed_amount', 'free_shipping']),
            'value' => $this->faker->randomFloat(2, 1, 100),
            'min_amount' => $this->faker->randomFloat(2, 1, 1000),
            'max_amount' => $this->faker->randomFloat(2, 1, 1000),
            'max_uses' => $this->faker->numberBetween(1, 100),
            'used_count' => $this->faker->numberBetween(0, 10),
            'starts_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'expires_at' => $this->faker->dateTimeBetween('+1 day', '+90 days'),
            'is_active' => $this->faker->boolean(70),
            'customer_ids' => $this->faker->optional(0.6)->randomElements([1, 2, 3, 4, 5], $this->faker->numberBetween(0, 3)),
            'product_ids' => $this->faker->optional(0.6)->randomElements([1, 2, 3, 4, 5], $this->faker->numberBetween(0, 3)),
            'category_ids' => $this->faker->optional(0.6)->randomElements([1, 2, 3, 4, 5], $this->faker->numberBetween(0, 3)),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
