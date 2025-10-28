<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\BankTransaction;
use Modules\Finance\Models\BankAccount;
use App\Models\User;

class BankTransactionFactory extends Factory
{
    protected $model = BankTransaction::class;

    public function definition(): array
    {
        $transactionType = $this->faker->randomElement(['debit', 'credit']);
        $amount = $transactionType === 'debit'
            ? $this->faker->randomFloat(2, 100, 50000)
            : -$this->faker->randomFloat(2, 100, 50000);

        return [
            'bank_account_id' => BankAccount::factory(),
            'transaction_date' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'amount' => $amount,
            'transaction_type' => $transactionType,
            'reference' => $this->faker->optional(0.7)->numerify('REF-######'),
            'description' => $this->faker->optional(0.9)->sentence(),
            'reconciliation_status' => 'unreconciled',
            'reconciled_by_id' => null,
            'reconciled_at' => null,
            'reconciliation_notes' => null,
            'statement_number' => $this->faker->optional(0.5)->numerify('STMT-####-##'),
            'running_balance' => $this->faker->randomFloat(2, 1000, 100000),
            'is_active' => true,
        ];
    }

    /**
     * State for reconciled transaction
     */
    public function reconciled(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'reconciliation_status' => 'reconciled',
                'reconciled_by_id' => User::factory(),
                'reconciled_at' => $this->faker->dateTimeBetween($attributes['transaction_date'], 'now'),
                'reconciliation_notes' => $this->faker->optional(0.6)->sentence(),
            ];
        });
    }

    /**
     * State for pending reconciliation
     */
    public function pending(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'reconciliation_status' => 'pending',
            ];
        });
    }

    /**
     * State for specific amount
     */
    public function withAmount(float $amount): static
    {
        return $this->state(function (array $attributes) use ($amount) {
            return [
                'amount' => $amount,
                'transaction_type' => $amount >= 0 ? 'debit' : 'credit',
            ];
        });
    }

    /**
     * State for specific date
     */
    public function onDate($date): static
    {
        return $this->state(function (array $attributes) use ($date) {
            return [
                'transaction_date' => $date,
            ];
        });
    }

    /**
     * State for debit transaction
     */
    public function debit(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'transaction_type' => 'debit',
                'amount' => abs($attributes['amount']),
            ];
        });
    }

    /**
     * State for credit transaction
     */
    public function credit(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'transaction_type' => 'credit',
                'amount' => -abs($attributes['amount']),
            ];
        });
    }

    /**
     * State for inactive transaction
     */
    public function inactive(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }
}
