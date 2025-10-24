<?php

namespace Modules\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounting\Models\ExchangeRate;

class ExchangeRateFactory extends Factory
{
    protected $model = ExchangeRate::class;

    public function definition(): array
    {
        $currencies = ['USD', 'EUR', 'MXN', 'GBP', 'CAD', 'JPY'];
        $fromCurrency = $this->faker->randomElement($currencies);
        $toCurrency = $this->faker->randomElement(array_diff($currencies, [$fromCurrency]));

        // More realistic exchange rates
        $rates = [
            'USD-MXN' => $this->faker->randomFloat(4, 16, 20),
            'EUR-MXN' => $this->faker->randomFloat(4, 18, 22),
            'USD-EUR' => $this->faker->randomFloat(4, 0.85, 0.95),
            'GBP-USD' => $this->faker->randomFloat(4, 1.20, 1.35),
            'CAD-USD' => $this->faker->randomFloat(4, 0.70, 0.78),
            'JPY-USD' => $this->faker->randomFloat(6, 0.0065, 0.0095),
        ];

        $rateKey = $fromCurrency . '-' . $toCurrency;
        $rate = $rates[$rateKey] ?? $this->faker->randomFloat(4, 0.5, 25);

        return [
            'from_currency' => $fromCurrency,
            'to_currency' => $toCurrency,
            'rate' => $rate,
            'effective_date' => $this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'source' => $this->faker->randomElement(['manual', 'banxico', 'ecb', 'api']),
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'metadata' => [],
        ];
    }

    /**
     * Active ExchangeRate
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Inactive ExchangeRate
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
