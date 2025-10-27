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
            'account_number' => $this->faker->unique()->numerify('##########'),
            'account_name' => $this->faker->words(3, true),
            'bank_name' => $this->faker->words(2, true),
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'MXN']),
            'gl_account_id' => $this->faker->numberBetween(1, 100),
            'current_balance' => $this->faker->randomFloat(2, 1, 100),
            'opening_balance' => $this->faker->randomFloat(2, 1, 100),
            'status' => $this->faker->randomElement(['active', 'inactive', 'pending']),
            'metadata' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
            'is_active' => $this->faker->boolean(70),
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
