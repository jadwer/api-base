<?php

namespace Modules\Ecommerce\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ecommerce\Models\Currency;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Ecommerce\Models\Currency>
 */
class CurrencyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Currency::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $currencies = [
            'USD' => ['name' => 'US Dollar', 'symbol' => '$', 'rate' => 1.000000],
            'EUR' => ['name' => 'Euro', 'symbol' => '€', 'rate' => 0.920000],
            'GBP' => ['name' => 'British Pound', 'symbol' => '£', 'rate' => 0.790000],
            'JPY' => ['name' => 'Japanese Yen', 'symbol' => '¥', 'rate' => 149.500000],
            'CAD' => ['name' => 'Canadian Dollar', 'symbol' => 'C$', 'rate' => 1.360000],
            'AUD' => ['name' => 'Australian Dollar', 'symbol' => 'A$', 'rate' => 1.530000],
            'CHF' => ['name' => 'Swiss Franc', 'symbol' => 'CHF', 'rate' => 0.890000],
            'CNY' => ['name' => 'Chinese Yuan', 'symbol' => '¥', 'rate' => 7.250000],
            'MXN' => ['name' => 'Mexican Peso', 'symbol' => '$', 'rate' => 17.800000],
            'BRL' => ['name' => 'Brazilian Real', 'symbol' => 'R$', 'rate' => 4.950000],
        ];

        $code = $this->faker->randomElement(array_keys($currencies));
        $currencyData = $currencies[$code];

        return [
            'code' => $code,
            'name' => $currencyData['name'],
            'symbol' => $currencyData['symbol'],
            'exchange_rate' => $currencyData['rate'],
            'is_active' => true,
            'is_default' => false,
        ];
    }

    /**
     * Indicate that the currency is the default currency
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
            'exchange_rate' => 1.000000, // Default currency always has rate 1
        ]);
    }

    /**
     * Indicate that the currency is inactive
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create USD currency
     */
    public function usd(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'exchange_rate' => 1.000000,
        ]);
    }

    /**
     * Create EUR currency
     */
    public function eur(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'EUR',
            'name' => 'Euro',
            'symbol' => '€',
            'exchange_rate' => 0.920000,
        ]);
    }

    /**
     * Create GBP currency
     */
    public function gbp(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'GBP',
            'name' => 'British Pound',
            'symbol' => '£',
            'exchange_rate' => 0.790000,
        ]);
    }
}
