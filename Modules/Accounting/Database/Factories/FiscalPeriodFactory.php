<?php

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Models\FiscalPeriod;

class FiscalPeriodFactory extends Factory
{
    protected $model = FiscalPeriod::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'year' => $this->faker->numberBetween(1, 100),
            'month' => $this->faker->numberBetween(1, 100),
            'start_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'end_date' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
            'status' => $this->faker->randomElement(['active', 'inactive', 'pending']),
            'closed_at' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
            'closed_by_id' => $this->faker->numberBetween(1, 100),
            'closing_entry_id' => $this->faker->numberBetween(1, 100),
            'metadata' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
        ];
    }

    /**
     * Active FiscalPeriod
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Inactive FiscalPeriod
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
