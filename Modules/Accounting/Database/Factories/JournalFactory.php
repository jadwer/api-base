<?php

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Models\Journal;

class JournalFactory extends Factory
{
    protected $model = Journal::class;

    public function definition(): array
    {
        $types = ['general', 'sales', 'purchases', 'cash_receipts', 'cash_disbursements', 'payroll'];
        $type = $this->faker->randomElement($types);
        $prefixes = [
            'general' => 'GJ',
            'sales' => 'SJ',
            'purchases' => 'PJ',
            'cash_receipts' => 'CR',
            'cash_disbursements' => 'CD',
            'payroll' => 'PR'
        ];

        return [
            'code' => $this->faker->unique()->regexify('[A-Z]{2}[0-9]{6}'),
            'name' => ucfirst(str_replace('_', ' ', $type)) . ' Journal',
            'description' => $this->faker->optional(0.6)->sentence(10),
            'prefix' => $prefixes[$type],
            'type' => $type,
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'metadata' => [],
        ];
    }

    /**
     * Active Journal
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Inactive Journal
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
