<?php

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Models\ExchangeRatePolicy;

class ExchangeRatePolicyFactory extends Factory
{
    protected $model = ExchangeRatePolicy::class;

    public function definition(): array
    {
        return [
            'company_id' => null,
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'MXN', 'GBP', 'CAD', 'JPY']),
            'source' => $this->faker->randomElement(['manual', 'banxico', 'ecb', 'api', 'auto']),
            'scope' => $this->faker->randomElement(['company', 'department', 'project']),
            'max_age_days' => $this->faker->randomElement([1, 7, 15, 30]),
            'tolerance_percentage' => $this->faker->randomFloat(2, 0, 10),
            'require_approval_over' => $this->faker->optional(0.5)->randomFloat(2, 1000, 100000),
            'is_active' => $this->faker->boolean(85),
            'metadata' => [],
        ];
    }

}
