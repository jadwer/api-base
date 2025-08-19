<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\BankAccount;

class BankAccountFactory extends Factory
{
    protected $model = BankAccount::class;

    public function definition(): array
    {
        return [
            'bank_name' => $this->faker->words(2, true),
            'account_number' => $this->faker->unique()->numerify('############'),
            'clabe' => $this->faker->sentence(3),
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'MXN']),
            'account_type' => $this->faker->numberBetween(1, 10),
            'opening_balance' => $this->faker->randomFloat(2, 1, 100),
            'status' => $this->faker->randomElement(['active', 'inactive', 'pending']),
        ];
    }

    /**
     * Active BankAccount
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Inactive BankAccount
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
