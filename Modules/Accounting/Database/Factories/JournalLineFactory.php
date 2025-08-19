<?php

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Models\JournalLine;

class JournalLineFactory extends Factory
{
    protected $model = JournalLine::class;

    public function definition(): array
    {
        return [
            'journal_entry_id' => \Modules\Accounting\Models\JournalEntry::factory(),
            'account_id' => \Modules\Accounting\Models\Account::factory(),
            'debit' => $this->faker->randomFloat(2, 1, 100),
            'credit' => $this->faker->randomFloat(2, 1, 100),
            'base_amount' => $this->faker->randomFloat(2, 1, 1000),
            'cost_center_id' => null,
            'partner_id' => null,
            'memo' => $this->faker->sentence(3),
        ];
    }

}
