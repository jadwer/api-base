<?php

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Models\Journal;

class JournalFactory extends Factory
{
    protected $model = Journal::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->lexify('????##'),
            'name' => $this->faker->words(2, true),
            'auto_numbering' => $this->faker->boolean(70),
            'sequence_prefix' => $this->faker->sentence(3),
            'sequence_next' => $this->faker->numberBetween(1, 100),
            'default_currency' => $this->faker->sentence(3),
            'post_policy' => $this->faker->sentence(3),
        ];
    }

}
