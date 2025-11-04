<?php

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Models\AccountMapping;

class AccountMappingFactory extends Factory
{
    protected $model = AccountMapping::class;

    public function definition(): array
    {
        $effectiveFrom = $this->faker->dateTimeBetween('-1 year', 'now');

        return [
            'company_id' => null,
            'mapping_type' => $this->faker->randomElement(['customer', 'supplier', 'tax', 'inventory', 'payroll', 'fixed_asset']),
            'account_id' => \Modules\Accounting\Models\Account::factory(),
            'version' => $this->faker->numberBetween(1, 5),
            'effective_from' => $effectiveFrom->format('Y-m-d'),
            'effective_to' => $this->faker->optional(0.3)->dateTimeBetween($effectiveFrom, '+1 year')?->format('Y-m-d'),
            'is_active' => $this->faker->boolean(80),
            'created_by_id' => \Modules\User\Models\User::factory(),
            'notes' => $this->faker->optional(0.5)->sentence(10),
            'metadata' => [],
        ];
    }

    /**
     * Inactive AccountMapping
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
