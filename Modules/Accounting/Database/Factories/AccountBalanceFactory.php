<?php

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Models\AccountBalance;

class AccountBalanceFactory extends Factory
{
    protected $model = AccountBalance::class;

    public function definition(): array
    {
        return [
            'company_id' => $this->faker->numberBetween(1, 100),
            'account_id' => $this->faker->numberBetween(1, 100),
            'fiscal_year' => $this->faker->numberBetween(1, 100),
            'fiscal_month' => $this->faker->numberBetween(1, 100),
            'opening_balance' => $this->faker->randomFloat(2, 1, 100),
            'period_debits' => $this->faker->randomFloat(2, 1, 100),
            'period_credits' => $this->faker->randomFloat(2, 1, 100),
            'closing_balance' => $this->faker->randomFloat(2, 1, 100),
        ];
    }

}
