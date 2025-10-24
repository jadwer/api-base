<?php

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Models\JournalEntry;

class JournalEntryFactory extends Factory
{
    protected $model = JournalEntry::class;

    public function definition(): array
    {
        return [
            'journal_id' => $this->faker->numberBetween(1, 100),
            'fiscal_period_id' => $this->faker->numberBetween(1, 100),
            'number' => $this->faker->sentence(3),
            'date' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
            'reference' => $this->faker->sentence(3),
            'description' => $this->faker->optional(0.7)->paragraph(),
            'total_debit' => $this->faker->randomFloat(2, 1, 1000),
            'total_credit' => $this->faker->randomFloat(2, 1, 1000),
            'company_id' => $this->faker->numberBetween(1, 100),
            'status' => $this->faker->randomElement(['active', 'inactive', 'pending']),
            'approved_at' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
            'approved_by_id' => $this->faker->numberBetween(1, 100),
            'posted_at' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
            'posted_by_id' => $this->faker->numberBetween(1, 100),
            'reversal_of_id' => $this->faker->numberBetween(1, 100),
            'reversal_reason' => $this->faker->optional(0.7)->paragraph(),
            'metadata' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
        ];
    }

    /**
     * Active JournalEntry
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Inactive JournalEntry
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
