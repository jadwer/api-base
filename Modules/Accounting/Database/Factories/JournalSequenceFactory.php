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
            'journal_id' => \Modules\Accounting\Models\Journal::factory(),
            'fiscal_year' => $this->faker->numberBetween(2020, 2025),
            'current_number' => $this->faker->numberBetween(0, 500),
            'metadata' => [],
        ];
    }

}
