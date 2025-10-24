<?php

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Models\JournalLine;

class JournalLineFactory extends Factory
{
    protected $model = JournalLine::class;

    public function definition(): array
    {
        $amount = $this->faker->randomFloat(2, 100, 5000);
        $isDebit = $this->faker->boolean();

        return [
            'journal_entry_id' => \Modules\Accounting\Models\JournalEntry::factory(),
            'account_id' => \Modules\Accounting\Models\Account::factory(),
            'contact_id' => null,
            'debit' => $isDebit ? $amount : 0,
            'credit' => $isDebit ? 0 : $amount,
            'description' => $this->faker->optional(0.6)->sentence(8),
            'reference' => $this->faker->optional(0.4)->bothify('REF-###'),
            'metadata' => [],
        ];
    }

}
