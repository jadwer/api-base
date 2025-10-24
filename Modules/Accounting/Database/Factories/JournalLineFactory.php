<?php

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Models\JournalLine;

class JournalLineFactory extends Factory
{
    protected $model = JournalLine::class;

    public function definition(): array
    {
        return [
            'journal_entry_id' => $this->faker->numberBetween(1, 100),
            'account_id' => $this->faker->numberBetween(1, 100),
            'contact_id' => $this->faker->numberBetween(1, 100),
            'debit' => $this->faker->randomFloat(2, 1, 100),
            'credit' => $this->faker->randomFloat(2, 1, 100),
            'description' => $this->faker->optional(0.7)->paragraph(),
            'reference' => $this->faker->sentence(3),
            'metadata' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
        ];
    }

}
