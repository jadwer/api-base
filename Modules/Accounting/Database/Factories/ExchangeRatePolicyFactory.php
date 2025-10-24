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
            'company_id' => $this->faker->numberBetween(1, 100),
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'MXN']),
            'source' => $this->faker->sentence(3),
            'scope' => $this->faker->sentence(3),
            'max_age_days' => $this->faker->numberBetween(1, 100),
            'tolerance_percentage' => $this->faker->randomFloat(2, 0, 100),
            'require_approval_over' => $this->faker->randomFloat(2, 1, 100),
            'is_active' => $this->faker->boolean(70),
        ];
    }

}
