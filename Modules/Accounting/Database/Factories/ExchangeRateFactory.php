<?php

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Models\ExchangeRate;

class ExchangeRateFactory extends Factory
{
    protected $model = ExchangeRate::class;

    public function definition(): array
    {
        return [
            'base_currency' => $this->faker->sentence(3),
            'quote_currency' => $this->faker->sentence(3),
            'rate_date' => $this->faker->randomFloat(2, 0, 100),
            'rate' => $this->faker->randomFloat(2, 0, 100),
        ];
    }

}
