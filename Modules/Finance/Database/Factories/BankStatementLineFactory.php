<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\BankStatementLine;

class BankStatementLineFactory extends Factory
{
    protected $model = BankStatementLine::class;

    public function definition(): array
    {
        return [
            'bank_statement_id' => \Modules\Finance\Models\BankStatement::factory(),
            'txn_date' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
            'amount' => $this->faker->randomFloat(2, 1, 1000),
            'counterparty' => $this->faker->numberBetween(1, 10),
            'reference' => $this->faker->sentence(3),
            'fitid' => $this->faker->sentence(3),
            'status' => $this->faker->randomElement(['active', 'inactive', 'pending']),
        ];
    }

    /**
     * Active BankStatementLine
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Inactive BankStatementLine
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
