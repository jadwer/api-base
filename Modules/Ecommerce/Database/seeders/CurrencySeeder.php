<?php

namespace Modules\Ecommerce\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Ecommerce\Models\Currency;

class CurrencySeeder extends Seeder
{
    /**
     * Seed the currencies table with major world currencies.
     * Exchange rates are approximate and based on October 2025 market rates.
     * All rates are relative to USD (base currency with rate 1.0)
     */
    public function run(): void
    {
        $currencies = [
            [
                'code' => 'USD',
                'name' => 'US Dollar',
                'symbol' => '$',
                'exchange_rate' => 1.0000,
                'is_active' => true,
                'is_default' => true,
            ],
            [
                'code' => 'EUR',
                'name' => 'Euro',
                'symbol' => '€',
                'exchange_rate' => 0.9200,
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'code' => 'GBP',
                'name' => 'British Pound',
                'symbol' => '£',
                'exchange_rate' => 0.7900,
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'code' => 'JPY',
                'name' => 'Japanese Yen',
                'symbol' => '¥',
                'exchange_rate' => 150.0000,
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'code' => 'CAD',
                'name' => 'Canadian Dollar',
                'symbol' => 'C$',
                'exchange_rate' => 1.3700,
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'code' => 'AUD',
                'name' => 'Australian Dollar',
                'symbol' => 'A$',
                'exchange_rate' => 1.5300,
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'code' => 'CHF',
                'name' => 'Swiss Franc',
                'symbol' => 'CHF',
                'exchange_rate' => 0.8800,
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'code' => 'CNY',
                'name' => 'Chinese Yuan',
                'symbol' => '¥',
                'exchange_rate' => 7.2400,
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'code' => 'MXN',
                'name' => 'Mexican Peso',
                'symbol' => 'MX$',
                'exchange_rate' => 17.5000,
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'code' => 'BRL',
                'name' => 'Brazilian Real',
                'symbol' => 'R$',
                'exchange_rate' => 4.9500,
                'is_active' => true,
                'is_default' => false,
            ],
        ];

        foreach ($currencies as $currency) {
            Currency::updateOrCreate(
                ['code' => $currency['code']],
                $currency
            );
        }

        $this->command->info('✅ Seeded 10 currencies with exchange rates');
    }
}
