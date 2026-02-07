<?php

namespace Modules\Ecommerce\Database\Factories;

use Modules\Ecommerce\Models\ProductView;
use Modules\Product\Models\Product;
use Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductViewFactory extends Factory
{
    protected $model = ProductView::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'user_id' => User::factory(),
            'session_id' => $this->faker->optional(0.3)->uuid(),
            'viewed_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * View from an anonymous session (no user).
     */
    public function anonymous(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'session_id' => $this->faker->uuid(),
        ]);
    }

    /**
     * View from a specific date range.
     */
    public function recent(int $days = 7): static
    {
        return $this->state(fn (array $attributes) => [
            'viewed_at' => $this->faker->dateTimeBetween("-{$days} days", 'now'),
        ]);
    }
}
