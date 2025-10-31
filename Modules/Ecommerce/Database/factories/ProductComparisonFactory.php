<?php

namespace Modules\Ecommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\Models\ProductComparison;
use Modules\User\Models\User;

class ProductComparisonFactory extends Factory
{
    protected $model = ProductComparison::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->words(3, true),
            'is_public' => $this->faker->boolean(30), // 30% public
        ];
    }

    /**
     * Make comparison public
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => true,
        ]);
    }

    /**
     * Make comparison private
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => false,
        ]);
    }

    /**
     * Add items to the comparison
     */
    public function hasItems(int $count = 1): static
    {
        return $this->has(
            \Modules\Ecommerce\Models\ProductComparisonItem::factory()->count($count),
            'items'
        );
    }
}
