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
            'journal_id' => \Modules\Accounting\Models\Journal::factory(),
            'period_id' => \Modules\Accounting\Models\FiscalPeriod::factory(),
            'number' => $this->faker->sentence(3),
            'date' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'MXN']),
            'exchange_rate' => $this->faker->randomFloat(2, 0, 100),
            'reference' => $this->faker->sentence(3),
            'description' => $this->faker->optional(0.7)->paragraph(),
            'status' => $this->faker->randomElement(['active', 'inactive', 'pending']),
            'approved_by_id' => null,
            'posted_by_id' => null,
            'posted_at' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
            'reversal_of_id' => null,
            'source_type' => $this->faker->sentence(3),
            'source_id' => $this->faker->numberBetween(1, 100),
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
