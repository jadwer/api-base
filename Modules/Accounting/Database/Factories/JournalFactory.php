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
            'description' => $this->faker->optional(0.7)->paragraph(),
            'prefix' => $this->faker->sentence(3),
            'type' => $this->faker->sentence(3),
            'status' => $this->faker->randomElement(['active', 'inactive', 'pending']),
            'metadata' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
        ];
    }

    /**
     * Active Journal
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Inactive Journal
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
