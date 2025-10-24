<?php

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Models\AccountBalance;

class AccountBalanceFactory extends Factory
{
    protected $model = AccountBalance::class;

    public function definition(): array
    {
        $openingBalance = $this->faker->randomFloat(2, -50000, 50000);
        $periodDebits = $this->faker->randomFloat(2, 0, 10000);
        $periodCredits = $this->faker->randomFloat(2, 0, 10000);
        $closingBalance = $openingBalance + $periodDebits - $periodCredits;

        return [
            'company_id' => null,
            'account_id' => \Modules\Accounting\Models\Account::factory(),
            'fiscal_year' => $this->faker->numberBetween(2020, 2025),
            'fiscal_month' => $this->faker->numberBetween(1, 12),
            'opening_balance' => $openingBalance,
            'period_debits' => $periodDebits,
            'period_credits' => $periodCredits,
            'closing_balance' => $closingBalance,
            'metadata' => [],
        ];
    }

}
