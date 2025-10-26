<?php

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Models\Account;

class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        // Generate valid account_type and matching nature
        $accountType = $this->faker->randomElement(['asset', 'liability', 'equity', 'revenue', 'expense', 'contra']);

        // Determine correct nature based on account type (accounting rules)
        $nature = match($accountType) {
            'asset', 'expense', 'contra' => 'debit',
            'liability', 'equity', 'revenue' => 'credit',
            default => 'debit'
        };

        return [
            'company_id' => null,
            'code' => $this->faker->unique()->numerify('####.###'),
            'name' => $this->faker->words(3, true),
            'account_type' => $accountType,
            'nature' => $nature,
            'level' => $this->faker->numberBetween(1, 4),
            'parent_id' => null,
            'currency' => 'MXN', // Always MXN for consistency
            'is_postable' => $this->faker->boolean(80),
            'is_cash_flow' => $this->faker->boolean(30),
            'status' => 'active',
            'metadata' => [],
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

    /**
     * Child Account with parent
     */
    public function parentAccount(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'parent_id' => Account::factory()->create()->id,
                'level' => $this->faker->numberBetween(2, 4),
            ];
        });
    }
}
