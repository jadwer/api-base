<?php

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Models\JournalSequence;

class JournalSequenceFactory extends Factory
{
    protected $model = JournalSequence::class;

    public function definition(): array
    {
        return [
            'journal_id' => $this->faker->numberBetween(1, 100),
            'fiscal_year' => $this->faker->numberBetween(1, 100),
            'current_number' => $this->faker->numberBetween(1, 100),
            'metadata' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
        ];
    }

}
