<?php

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Models\ExchangeRate;

class ExchangeRateFactory extends Factory
{
    protected $model = ExchangeRate::class;

    public function definition(): array
    {
        return [
            'from_currency' => $this->faker->sentence(3),
            'to_currency' => $this->faker->sentence(3),
            'rate' => $this->faker->randomFloat(2, 0, 100),
            'effective_date' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
            'source' => $this->faker->sentence(3),
            'status' => $this->faker->randomElement(['active', 'inactive', 'pending']),
            'metadata' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
        ];
    }

    /**
     * Active ExchangeRate
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Inactive ExchangeRate
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
