<?php

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Models\Account;

class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        return [
            'company_id' => $this->faker->numberBetween(1, 100),
            'code' => $this->faker->lexify('????##'),
            'name' => $this->faker->words(2, true),
            'account_type' => $this->faker->numberBetween(1, 10),
            'nature' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
            'level' => $this->faker->numberBetween(1, 100),
            'parent_id' => $this->faker->numberBetween(1, 100),
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'MXN']),
            'is_postable' => $this->faker->boolean(70),
            'is_cash_flow' => $this->faker->boolean(70),
            'status' => $this->faker->randomElement(['active', 'inactive', 'pending']),
            'metadata' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
        ];
    }

    /**
     * Active Account
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Inactive Account
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
