<?php

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Models\JournalEntry;

class JournalEntryFactory extends Factory
{
    protected $model = JournalEntry::class;

    public function definition(): array
    {
        $totalAmount = $this->faker->randomFloat(2, 100, 10000);

        return [
            'journal_id' => \Modules\Accounting\Models\Journal::factory(),
            'fiscal_period_id' => \Modules\Accounting\Models\FiscalPeriod::factory(),
            'number' => $this->faker->optional(0.8)->bothify('JE-####'),
            'date' => $this->faker->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'reference' => $this->faker->optional(0.6)->bothify('REF-###'),
            'description' => $this->faker->optional(0.7)->sentence(10),
            'total_debit' => $totalAmount,
            'total_credit' => $totalAmount,
            'company_id' => null,
            'status' => $this->faker->randomElement(['draft', 'approved', 'posted', 'reversed']),
            'approved_at' => null,
            'approved_by_id' => null,
            'posted_at' => null,
            'posted_by_id' => null,
            'reversal_of_id' => null,
            'reversal_reason' => null,
            'metadata' => [],
        ];
    }

    /**
     * Draft JournalEntry
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'approved_at' => null,
            'approved_by_id' => null,
            'posted_at' => null,
            'posted_by_id' => null,
        ]);
    }

    /**
     * Posted JournalEntry
     */
    public function posted(): static
    {
        $approvedAt = $this->faker->dateTimeBetween('-30 days', 'now');
        $postedAt = $this->faker->dateTimeBetween($approvedAt, 'now');

        return $this->state(function (array $attributes) use ($approvedAt, $postedAt) {
            $user = \Modules\User\Models\User::factory()->create();
            return [
                'status' => 'posted',
                'approved_at' => $approvedAt,
                'approved_by_id' => $user->id,
                'posted_at' => $postedAt,
                'posted_by_id' => $user->id,
            ];
        });
    }
}
