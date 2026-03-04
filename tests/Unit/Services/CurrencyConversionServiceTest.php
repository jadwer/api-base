<?php

namespace Tests\Unit\Services;

use App\Services\ConversionResult;
use App\Services\CurrencyConversionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\AppConfig\Models\AppSetting;
use Modules\Ecommerce\Models\Currency;
use Tests\TestCase;

class CurrencyConversionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CurrencyConversionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new CurrencyConversionService();

        // Create base currencies for most tests
        // MXN = base currency, exchange_rate = 1.0 (1 MXN = 1 MXN)
        // USD: 1 USD = 17.5 MXN
        // EUR: 1 EUR = 19.0 MXN
        Currency::query()->delete();

        Currency::create([
            'code' => 'MXN',
            'name' => 'Mexican Peso',
            'symbol' => '$',
            'exchange_rate' => 1.0,
            'is_active' => true,
            'is_default' => true,
        ]);

        Currency::create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'exchange_rate' => 17.5,
            'is_active' => true,
            'is_default' => false,
        ]);

        Currency::create([
            'code' => 'EUR',
            'name' => 'Euro',
            'symbol' => "\u{20AC}",
            'exchange_rate' => 19.0,
            'is_active' => true,
            'is_default' => false,
        ]);

        // Ensure no margin by default (clear any cached settings)
        Cache::flush();
    }

    // ---------------------------------------------------------------
    // convert() - Conversion between currencies
    // ---------------------------------------------------------------

    public function test_convert_usd_to_mxn(): void
    {
        $result = $this->service->convert(100.0, 'USD', 'MXN');

        $this->assertInstanceOf(ConversionResult::class, $result);
        $this->assertEquals(100.0, $result->originalAmount);
        // 100 * (17.5 / 1.0) = 1750.00
        $this->assertEquals(1750.00, $result->convertedAmount);
        $this->assertEquals('USD', $result->fromCurrency);
        $this->assertEquals('MXN', $result->toCurrency);
        $this->assertEqualsWithDelta(17.5, $result->exchangeRate, 0.000001);
    }

    public function test_convert_mxn_to_usd(): void
    {
        $result = $this->service->convert(1750.0, 'MXN', 'USD');

        $this->assertInstanceOf(ConversionResult::class, $result);
        $this->assertEquals(1750.0, $result->originalAmount);
        // 1750 * (1.0 / 17.5) = 100.00
        $this->assertEquals(100.00, $result->convertedAmount);
        $this->assertEquals('MXN', $result->fromCurrency);
        $this->assertEquals('USD', $result->toCurrency);
        $this->assertEqualsWithDelta(1.0 / 17.5, $result->exchangeRate, 0.000001);
    }

    public function test_convert_eur_to_mxn(): void
    {
        $result = $this->service->convert(100.0, 'EUR', 'MXN');

        // 100 * (19.0 / 1.0) = 1900.00
        $this->assertEquals(1900.00, $result->convertedAmount);
        $this->assertEquals('EUR', $result->fromCurrency);
        $this->assertEquals('MXN', $result->toCurrency);
    }

    public function test_convert_usd_to_eur(): void
    {
        $result = $this->service->convert(100.0, 'USD', 'EUR');

        // 100 * (17.5 / 19.0) = 92.105263...
        // rawRate = 17.5 / 19.0 = 0.921053
        // convertedAmount = round(100 * 0.921053, 2) = 92.11
        $expectedRate = 17.5 / 19.0;
        $expectedAmount = round(100.0 * $expectedRate, 2);

        $this->assertEquals($expectedAmount, $result->convertedAmount);
        $this->assertEqualsWithDelta($expectedRate, $result->exchangeRate, 0.000001);
        $this->assertEquals('USD', $result->fromCurrency);
        $this->assertEquals('EUR', $result->toCurrency);
    }

    public function test_convert_eur_to_usd(): void
    {
        $result = $this->service->convert(100.0, 'EUR', 'USD');

        // 100 * (19.0 / 17.5) = 108.571428...
        $expectedRate = 19.0 / 17.5;
        $expectedAmount = round(100.0 * $expectedRate, 2);

        $this->assertEquals($expectedAmount, $result->convertedAmount);
        $this->assertEqualsWithDelta($expectedRate, $result->exchangeRate, 0.000001);
    }

    // ---------------------------------------------------------------
    // convert() - Same currency returns same amount (no conversion)
    // ---------------------------------------------------------------

    public function test_convert_same_currency_returns_same_amount(): void
    {
        $result = $this->service->convert(123.45, 'USD', 'USD');

        $this->assertInstanceOf(ConversionResult::class, $result);
        $this->assertEquals(123.45, $result->originalAmount);
        $this->assertEquals(123.45, $result->convertedAmount);
        $this->assertEquals(1.0, $result->exchangeRate);
        $this->assertEquals(1.0, $result->effectiveRate);
        $this->assertEquals(0.0, $result->marginApplied);
        $this->assertEquals('USD', $result->fromCurrency);
        $this->assertEquals('USD', $result->toCurrency);
    }

    public function test_convert_same_currency_does_not_query_database(): void
    {
        // Even a non-existent currency code should work for same-currency conversion
        // because the service short-circuits before hitting the DB
        $result = $this->service->convert(50.0, 'XYZ', 'XYZ');

        $this->assertEquals(50.0, $result->convertedAmount);
        $this->assertEquals(1.0, $result->exchangeRate);
    }

    public function test_convert_same_currency_mxn(): void
    {
        $result = $this->service->convert(999.99, 'MXN', 'MXN');

        $this->assertEquals(999.99, $result->convertedAmount);
        $this->assertEquals('MXN', $result->fromCurrency);
        $this->assertEquals('MXN', $result->toCurrency);
    }

    // ---------------------------------------------------------------
    // convert() - Applies margin correctly
    // ---------------------------------------------------------------

    public function test_convert_applies_margin_percentage(): void
    {
        // Set a 2% margin
        AppSetting::create([
            'key' => 'currency.exchange_rate_margin',
            'value' => '2',
            'type' => 'string',
            'group' => 'currency',
        ]);
        Cache::flush();

        $result = $this->service->convert(100.0, 'USD', 'MXN');

        // rawRate = 17.5 / 1.0 = 17.5
        // effectiveRate = 17.5 * (1 + 2/100) = 17.5 * 1.02 = 17.85
        // convertedAmount = round(100 * 17.85, 2) = 1785.00
        $this->assertEquals(1785.00, $result->convertedAmount);
        $this->assertEqualsWithDelta(17.5, $result->exchangeRate, 0.000001);
        $this->assertEquals(2.0, $result->marginApplied);
        $this->assertEqualsWithDelta(17.85, $result->effectiveRate, 0.000001);
    }

    public function test_convert_applies_five_percent_margin(): void
    {
        AppSetting::create([
            'key' => 'currency.exchange_rate_margin',
            'value' => '5',
            'type' => 'string',
            'group' => 'currency',
        ]);
        Cache::flush();

        $result = $this->service->convert(200.0, 'USD', 'MXN');

        // rawRate = 17.5
        // effectiveRate = 17.5 * 1.05 = 18.375
        // convertedAmount = round(200 * 18.375, 2) = 3675.00
        $this->assertEquals(3675.00, $result->convertedAmount);
        $this->assertEquals(5.0, $result->marginApplied);
        $this->assertEqualsWithDelta(18.375, $result->effectiveRate, 0.000001);
    }

    public function test_convert_zero_margin_does_not_alter_rate(): void
    {
        // No AppSetting created = default 0 margin
        $result = $this->service->convert(100.0, 'USD', 'MXN');

        $this->assertEquals(1750.00, $result->convertedAmount);
        $this->assertEquals(0.0, $result->marginApplied);
        $this->assertEqualsWithDelta($result->exchangeRate, $result->effectiveRate, 0.000001);
    }

    public function test_convert_margin_applies_to_cross_currency_pair(): void
    {
        AppSetting::create([
            'key' => 'currency.exchange_rate_margin',
            'value' => '3',
            'type' => 'string',
            'group' => 'currency',
        ]);
        Cache::flush();

        $result = $this->service->convert(100.0, 'EUR', 'USD');

        // rawRate = 19.0 / 17.5 = 1.085714...
        // effectiveRate = rawRate * 1.03
        $rawRate = 19.0 / 17.5;
        $effectiveRate = $rawRate * 1.03;
        $expectedAmount = round(100.0 * $effectiveRate, 2);

        $this->assertEquals($expectedAmount, $result->convertedAmount);
        $this->assertEquals(3.0, $result->marginApplied);
    }

    // ---------------------------------------------------------------
    // convert() - Returns ConversionResult with correct structure
    // ---------------------------------------------------------------

    public function test_convert_returns_conversion_result_with_all_fields(): void
    {
        $result = $this->service->convert(250.0, 'USD', 'MXN');

        $this->assertInstanceOf(ConversionResult::class, $result);
        $this->assertIsFloat($result->originalAmount);
        $this->assertIsFloat($result->convertedAmount);
        $this->assertIsString($result->fromCurrency);
        $this->assertIsString($result->toCurrency);
        $this->assertIsFloat($result->exchangeRate);
        $this->assertIsFloat($result->marginApplied);
        $this->assertIsFloat($result->effectiveRate);
        $this->assertInstanceOf(\Carbon\Carbon::class, $result->rateDate);
    }

    public function test_conversion_result_to_array(): void
    {
        $result = $this->service->convert(100.0, 'USD', 'MXN');
        $array = $result->toArray();

        $this->assertArrayHasKey('original_amount', $array);
        $this->assertArrayHasKey('converted_amount', $array);
        $this->assertArrayHasKey('from_currency', $array);
        $this->assertArrayHasKey('to_currency', $array);
        $this->assertArrayHasKey('exchange_rate', $array);
        $this->assertArrayHasKey('margin_applied', $array);
        $this->assertArrayHasKey('effective_rate', $array);
        $this->assertArrayHasKey('rate_date', $array);

        $this->assertEquals(100.0, $array['original_amount']);
        $this->assertEquals(1750.00, $array['converted_amount']);
        $this->assertEquals('USD', $array['from_currency']);
        $this->assertEquals('MXN', $array['to_currency']);
    }

    // ---------------------------------------------------------------
    // getExchangeRate() - Returns correct rate
    // ---------------------------------------------------------------

    public function test_get_exchange_rate_usd_to_mxn(): void
    {
        $rate = $this->service->getExchangeRate('USD', 'MXN');

        // USD rate=17.5, MXN rate=1.0 => 17.5/1.0 = 17.5
        $this->assertEqualsWithDelta(17.5, $rate, 0.000001);
    }

    public function test_get_exchange_rate_mxn_to_usd(): void
    {
        $rate = $this->service->getExchangeRate('MXN', 'USD');

        // MXN rate=1.0, USD rate=17.5 => 1.0/17.5
        $this->assertEqualsWithDelta(1.0 / 17.5, $rate, 0.000001);
    }

    public function test_get_exchange_rate_usd_to_eur(): void
    {
        $rate = $this->service->getExchangeRate('USD', 'EUR');

        // USD rate=17.5, EUR rate=19.0 => 17.5/19.0
        $this->assertEqualsWithDelta(17.5 / 19.0, $rate, 0.000001);
    }

    public function test_get_exchange_rate_same_currency(): void
    {
        $rate = $this->service->getExchangeRate('USD', 'USD');

        $this->assertEquals(1.0, $rate);
    }

    public function test_get_exchange_rate_eur_to_mxn(): void
    {
        $rate = $this->service->getExchangeRate('EUR', 'MXN');

        $this->assertEqualsWithDelta(19.0, $rate, 0.000001);
    }

    // ---------------------------------------------------------------
    // getEffectiveRate() - Includes margin
    // ---------------------------------------------------------------

    public function test_get_effective_rate_without_margin(): void
    {
        // No margin setting = default 0%
        $rate = $this->service->getEffectiveRate('USD', 'MXN');

        // With 0 margin, effective = raw rate
        $this->assertEqualsWithDelta(17.5, $rate, 0.000001);
    }

    public function test_get_effective_rate_with_margin(): void
    {
        AppSetting::create([
            'key' => 'currency.exchange_rate_margin',
            'value' => '2',
            'type' => 'string',
            'group' => 'currency',
        ]);
        Cache::flush();

        $rate = $this->service->getEffectiveRate('USD', 'MXN');

        // rawRate = 17.5, margin = 2%
        // effectiveRate = 17.5 * 1.02 = 17.85
        $this->assertEqualsWithDelta(17.85, $rate, 0.000001);
    }

    public function test_get_effective_rate_same_currency(): void
    {
        AppSetting::create([
            'key' => 'currency.exchange_rate_margin',
            'value' => '5',
            'type' => 'string',
            'group' => 'currency',
        ]);
        Cache::flush();

        $rate = $this->service->getEffectiveRate('MXN', 'MXN');

        // Same currency: rawRate=1.0, with 5% margin = 1.05
        $this->assertEqualsWithDelta(1.05, $rate, 0.000001);
    }

    public function test_get_effective_rate_with_ten_percent_margin(): void
    {
        AppSetting::create([
            'key' => 'currency.exchange_rate_margin',
            'value' => '10',
            'type' => 'string',
            'group' => 'currency',
        ]);
        Cache::flush();

        $rate = $this->service->getEffectiveRate('EUR', 'USD');

        // rawRate = 19.0/17.5 = 1.085714...
        // effectiveRate = rawRate * 1.10
        $expectedRate = (19.0 / 17.5) * 1.10;
        $this->assertEqualsWithDelta($expectedRate, $rate, 0.000001);
    }

    // ---------------------------------------------------------------
    // getMarginPercentage() - Reads from AppConfig
    // ---------------------------------------------------------------

    public function test_get_margin_percentage_default_is_zero(): void
    {
        // No AppSetting row for margin
        $margin = $this->service->getMarginPercentage();

        $this->assertEquals(0.0, $margin);
    }

    public function test_get_margin_percentage_reads_from_app_setting(): void
    {
        AppSetting::create([
            'key' => 'currency.exchange_rate_margin',
            'value' => '3.5',
            'type' => 'string',
            'group' => 'currency',
        ]);
        Cache::flush();

        $margin = $this->service->getMarginPercentage();

        $this->assertEquals(3.5, $margin);
    }

    public function test_get_margin_percentage_returns_float(): void
    {
        AppSetting::create([
            'key' => 'currency.exchange_rate_margin',
            'value' => '7',
            'type' => 'string',
            'group' => 'currency',
        ]);
        Cache::flush();

        $margin = $this->service->getMarginPercentage();

        $this->assertIsFloat($margin);
        $this->assertEquals(7.0, $margin);
    }

    // ---------------------------------------------------------------
    // getBaseCurrencyCode()
    // ---------------------------------------------------------------

    public function test_get_base_currency_code_default_is_mxn(): void
    {
        $code = $this->service->getBaseCurrencyCode();

        $this->assertEquals('MXN', $code);
    }

    public function test_get_base_currency_code_reads_from_app_setting(): void
    {
        AppSetting::create([
            'key' => 'currency.base_currency',
            'value' => 'USD',
            'type' => 'string',
            'group' => 'currency',
        ]);
        Cache::flush();

        $code = $this->service->getBaseCurrencyCode();

        $this->assertEquals('USD', $code);
    }

    // ---------------------------------------------------------------
    // Error cases - Invalid currency throws exception
    // ---------------------------------------------------------------

    public function test_convert_throws_exception_for_invalid_from_currency(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency not found or inactive: XYZ');

        $this->service->convert(100.0, 'XYZ', 'MXN');
    }

    public function test_convert_throws_exception_for_invalid_to_currency(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency not found or inactive: XYZ');

        $this->service->convert(100.0, 'USD', 'XYZ');
    }

    public function test_convert_throws_exception_for_inactive_from_currency(): void
    {
        Currency::where('code', 'USD')->update(['is_active' => false]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency not found or inactive: USD');

        $this->service->convert(100.0, 'USD', 'MXN');
    }

    public function test_convert_throws_exception_for_inactive_to_currency(): void
    {
        Currency::where('code', 'EUR')->update(['is_active' => false]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency not found or inactive: EUR');

        $this->service->convert(100.0, 'USD', 'EUR');
    }

    public function test_convert_throws_division_by_zero_for_zero_from_rate(): void
    {
        Currency::where('code', 'USD')->update(['exchange_rate' => 0]);

        $this->expectException(\DivisionByZeroError::class);
        $this->expectExceptionMessage('Exchange rate is zero for currency: USD');

        $this->service->convert(100.0, 'USD', 'MXN');
    }

    public function test_convert_throws_division_by_zero_for_zero_to_rate(): void
    {
        Currency::where('code', 'MXN')->update(['exchange_rate' => 0]);

        $this->expectException(\DivisionByZeroError::class);
        $this->expectExceptionMessage('Exchange rate is zero for currency: MXN');

        $this->service->convert(100.0, 'USD', 'MXN');
    }

    public function test_get_exchange_rate_throws_for_nonexistent_from_currency(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->service->getExchangeRate('ABC', 'MXN');
    }

    public function test_get_exchange_rate_throws_for_nonexistent_to_currency(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->service->getExchangeRate('USD', 'ABC');
    }

    public function test_get_exchange_rate_throws_for_inactive_currency(): void
    {
        Currency::where('code', 'EUR')->update(['is_active' => false]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->service->getExchangeRate('EUR', 'MXN');
    }

    // ---------------------------------------------------------------
    // Edge cases
    // ---------------------------------------------------------------

    public function test_convert_zero_amount(): void
    {
        $result = $this->service->convert(0.0, 'USD', 'MXN');

        $this->assertEquals(0.0, $result->originalAmount);
        $this->assertEquals(0.00, $result->convertedAmount);
    }

    public function test_convert_very_small_amount(): void
    {
        $result = $this->service->convert(0.01, 'USD', 'MXN');

        // 0.01 * 17.5 = 0.175 => round to 0.18
        $this->assertEquals(0.18, $result->convertedAmount);
    }

    public function test_convert_large_amount(): void
    {
        $result = $this->service->convert(1000000.0, 'USD', 'MXN');

        // 1,000,000 * 17.5 = 17,500,000
        $this->assertEquals(17500000.00, $result->convertedAmount);
    }

    public function test_convert_rounds_to_two_decimals(): void
    {
        // Use a rate that produces many decimal places
        // 100 USD -> EUR: 100 * (17.5 / 19.0) = 92.10526315...
        $result = $this->service->convert(100.0, 'USD', 'EUR');

        // Verify it's rounded to 2 decimal places
        $this->assertEquals(round(100.0 * (17.5 / 19.0), 2), $result->convertedAmount);
        $parts = explode('.', (string) $result->convertedAmount);
        if (isset($parts[1])) {
            $this->assertLessThanOrEqual(2, strlen($parts[1]));
        }
    }
}
