<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\BankStatement;

class BankStatementFactory extends Factory
{
    protected $model = BankStatement::class;

    public function definition(): array
    {
        return [
            'bank_account_id' => \Modules\Finance\Models\BankAccount::factory(),
            'statement_date' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
            'import_source' => $this->faker->sentence(3),
        ];
    }

}
