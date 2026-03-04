<?php

namespace Tests\Unit\Services;

use App\Services\BanxicoExchangeRateProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\AppConfig\Models\AppSetting;
use Modules\Ecommerce\Models\Currency;
use Tests\TestCase;

class BanxicoExchangeRateProviderTest extends TestCase
{
    use RefreshDatabase;

    private BanxicoExchangeRateProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new BanxicoExchangeRateProvider();
    }

    /**
     * Helper to create the Banxico API token in AppSetting.
     */
    private function setApiToken(string $token = 'test-banxico-token'): void
    {
        AppSetting::create([
            'key' => 'currency.banxico_api_token',
            'value' => $token,
            'type' => 'string',
            'group' => 'currency',
        ]);
    }

    /**
     * Helper to build a fake Banxico API response body.
     */
    private function fakeBanxicoResponse(string $dato): array
    {
        return [
            'bmx' => [
                'series' => [
                    [
                        'idSerie' => 'SF43718',
                        'titulo' => 'Tipo de cambio',
                        'datos' => [
                            [
                                'fecha' => '03/03/2026',
                                'dato' => $dato,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    // -------------------------------------------------------
    // fetchUSDRate
    // -------------------------------------------------------

    public function test_fetch_usd_rate_returns_float_on_success(): void
    {
        $this->setApiToken();

        Http::fake([
            'www.banxico.org.mx/SieAPIRest/service/v1/series/SF43718/datos/oportuno' => Http::response(
                $this->fakeBanxicoResponse('17.5432'),
                200
            ),
        ]);

        $rate = $this->provider->fetchUSDRate();

        $this->assertNotNull($rate);
        $this->assertIsFloat($rate);
        $this->assertEquals(17.5432, $rate);
    }

    public function test_fetch_usd_rate_sends_bmx_token_header(): void
    {
        $this->setApiToken('my-secret-token');

        Http::fake([
            'www.banxico.org.mx/*' => Http::response(
                $this->fakeBanxicoResponse('17.00'),
                200
            ),
        ]);

        $this->provider->fetchUSDRate();

        Http::assertSent(function ($request) {
            return $request->hasHeader('Bmx-Token', 'my-secret-token');
        });
    }

    public function test_fetch_usd_rate_uses_correct_series_id(): void
    {
        $this->setApiToken();

        Http::fake([
            'www.banxico.org.mx/SieAPIRest/service/v1/series/SF43718/datos/oportuno' => Http::response(
                $this->fakeBanxicoResponse('17.00'),
                200
            ),
        ]);

        $rate = $this->provider->fetchUSDRate();

        $this->assertNotNull($rate);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'SF43718');
        });
    }

    // -------------------------------------------------------
    // fetchEURRate
    // -------------------------------------------------------

    public function test_fetch_eur_rate_returns_float_on_success(): void
    {
        $this->setApiToken();

        Http::fake([
            'www.banxico.org.mx/SieAPIRest/service/v1/series/SF46410/datos/oportuno' => Http::response(
                [
                    'bmx' => [
                        'series' => [
                            [
                                'idSerie' => 'SF46410',
                                'titulo' => 'Cotizacion EUR/MXN',
                                'datos' => [
                                    [
                                        'fecha' => '03/03/2026',
                                        'dato' => '19.2145',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                200
            ),
        ]);

        $rate = $this->provider->fetchEURRate();

        $this->assertNotNull($rate);
        $this->assertIsFloat($rate);
        $this->assertEquals(19.2145, $rate);
    }

    public function test_fetch_eur_rate_uses_correct_series_id(): void
    {
        $this->setApiToken();

        Http::fake([
            'www.banxico.org.mx/SieAPIRest/service/v1/series/SF46410/datos/oportuno' => Http::response(
                $this->fakeBanxicoResponse('19.00'),
                200
            ),
        ]);

        $this->provider->fetchEURRate();

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'SF46410');
        });
    }

    // -------------------------------------------------------
    // fetchRate - general
    // -------------------------------------------------------

    public function test_fetch_rate_handles_comma_in_dato_value(): void
    {
        $this->setApiToken();

        Http::fake([
            'www.banxico.org.mx/*' => Http::response(
                $this->fakeBanxicoResponse('1,234.56'),
                200
            ),
        ]);

        $rate = $this->provider->fetchUSDRate();

        $this->assertNotNull($rate);
        $this->assertEquals(1234.56, $rate);
    }

    public function test_fetch_rate_returns_last_entry_when_multiple_datos(): void
    {
        $this->setApiToken();

        Http::fake([
            'www.banxico.org.mx/*' => Http::response(
                [
                    'bmx' => [
                        'series' => [
                            [
                                'datos' => [
                                    ['fecha' => '01/03/2026', 'dato' => '17.00'],
                                    ['fecha' => '02/03/2026', 'dato' => '17.25'],
                                    ['fecha' => '03/03/2026', 'dato' => '17.50'],
                                ],
                            ],
                        ],
                    ],
                ],
                200
            ),
        ]);

        $rate = $this->provider->fetchUSDRate();

        $this->assertEquals(17.50, $rate);
    }

    public function test_fetch_rate_returns_null_for_unsupported_currency(): void
    {
        $this->setApiToken();

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'No series ID for currency GBP');
            });

        $rate = $this->provider->fetchRate('GBP');

        $this->assertNull($rate);
    }

    // -------------------------------------------------------
    // API error handling
    // -------------------------------------------------------

    public function test_fetch_rate_returns_null_on_http_error(): void
    {
        $this->setApiToken();

        Http::fake([
            'www.banxico.org.mx/*' => Http::response(
                ['error' => 'Unauthorized'],
                401
            ),
        ]);

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'HTTP 401 for USD');
            });

        $rate = $this->provider->fetchUSDRate();

        $this->assertNull($rate);
    }

    public function test_fetch_rate_returns_null_on_http_500(): void
    {
        $this->setApiToken();

        Http::fake([
            'www.banxico.org.mx/*' => Http::response(
                ['error' => 'Internal Server Error'],
                500
            ),
        ]);

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'HTTP 500 for USD');
            });

        $rate = $this->provider->fetchUSDRate();

        $this->assertNull($rate);
    }

    public function test_fetch_rate_returns_null_when_empty_series_data(): void
    {
        $this->setApiToken();

        Http::fake([
            'www.banxico.org.mx/*' => Http::response(
                [
                    'bmx' => [
                        'series' => [
                            [
                                'datos' => [],
                            ],
                        ],
                    ],
                ],
                200
            ),
        ]);

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'No data returned for USD');
            });

        $rate = $this->provider->fetchUSDRate();

        $this->assertNull($rate);
    }

    public function test_fetch_rate_returns_null_when_dato_is_non_numeric(): void
    {
        $this->setApiToken();

        Http::fake([
            'www.banxico.org.mx/*' => Http::response(
                $this->fakeBanxicoResponse('N/E'),
                200
            ),
        ]);

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'Invalid rate value');
            });

        $rate = $this->provider->fetchUSDRate();

        $this->assertNull($rate);
    }

    public function test_fetch_rate_returns_null_when_dato_is_zero(): void
    {
        $this->setApiToken();

        Http::fake([
            'www.banxico.org.mx/*' => Http::response(
                $this->fakeBanxicoResponse('0'),
                200
            ),
        ]);

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'Invalid rate value');
            });

        $rate = $this->provider->fetchUSDRate();

        $this->assertNull($rate);
    }

    public function test_fetch_rate_returns_null_on_connection_exception(): void
    {
        $this->setApiToken();

        Http::fake([
            'www.banxico.org.mx/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
            },
        ]);

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'Connection timed out');
            });

        $rate = $this->provider->fetchUSDRate();

        $this->assertNull($rate);
    }

    // -------------------------------------------------------
    // Missing / invalid API token
    // -------------------------------------------------------

    public function test_fetch_rate_returns_null_when_no_api_token_configured(): void
    {
        // Do NOT set any token

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'No API token configured');
            });

        $rate = $this->provider->fetchUSDRate();

        $this->assertNull($rate);
        Http::assertNothingSent();
    }

    public function test_fetch_rate_returns_null_when_api_token_is_empty_string(): void
    {
        AppSetting::create([
            'key' => 'currency.banxico_api_token',
            'value' => '',
            'type' => 'string',
            'group' => 'currency',
        ]);

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'No API token configured');
            });

        $rate = $this->provider->fetchUSDRate();

        $this->assertNull($rate);
        Http::assertNothingSent();
    }

    // -------------------------------------------------------
    // updateAllRates
    // -------------------------------------------------------

    public function test_update_all_rates_updates_currency_models(): void
    {
        $this->setApiToken();

        $usd = Currency::firstOrCreate(['code' => 'USD'], [
            'name' => 'US Dollar',
            'symbol' => '$',
            'exchange_rate' => 16.00,
            'is_active' => true,
            'is_default' => false,
        ]);
        $usd->update(['exchange_rate' => 16.00, 'is_active' => true]);

        $eur = Currency::firstOrCreate(['code' => 'EUR'], [
            'name' => 'Euro',
            'symbol' => 'E',
            'exchange_rate' => 18.00,
            'is_active' => true,
            'is_default' => false,
        ]);
        $eur->update(['exchange_rate' => 18.00, 'is_active' => true]);

        Http::fake([
            'www.banxico.org.mx/SieAPIRest/service/v1/series/SF43718/datos/oportuno' => Http::response(
                $this->fakeBanxicoResponse('17.54'),
                200
            ),
            'www.banxico.org.mx/SieAPIRest/service/v1/series/SF46410/datos/oportuno' => Http::response(
                [
                    'bmx' => [
                        'series' => [
                            [
                                'datos' => [
                                    ['fecha' => '03/03/2026', 'dato' => '19.21'],
                                ],
                            ],
                        ],
                    ],
                ],
                200
            ),
        ]);

        $results = $this->provider->updateAllRates();

        $usd->refresh();
        $eur->refresh();

        $this->assertEquals(17.54, $usd->exchange_rate);
        $this->assertEquals(19.21, $eur->exchange_rate);
        $this->assertStringContains('updated', $results['USD']);
        $this->assertStringContains('updated', $results['EUR']);
    }

    public function test_update_all_rates_skips_inactive_currencies(): void
    {
        $this->setApiToken();

        $usd = Currency::firstOrCreate(['code' => 'USD'], [
            'name' => 'US Dollar',
            'symbol' => '$',
            'exchange_rate' => 16.00,
            'is_active' => false,
            'is_default' => false,
        ]);
        $usd->update(['is_active' => false]);

        Http::fake([
            'www.banxico.org.mx/*' => Http::response(
                $this->fakeBanxicoResponse('17.54'),
                200
            ),
        ]);

        $results = $this->provider->updateAllRates();

        $this->assertStringContains('skipped', $results['USD']);
    }

    public function test_update_all_rates_skips_currencies_not_in_database(): void
    {
        $this->setApiToken();

        // Remove USD and EUR so they don't exist in the database
        Currency::where('code', 'USD')->delete();
        Currency::where('code', 'EUR')->delete();

        Http::fake([
            'www.banxico.org.mx/*' => Http::response(
                $this->fakeBanxicoResponse('17.54'),
                200
            ),
        ]);

        $results = $this->provider->updateAllRates();

        $this->assertStringContains('skipped', $results['USD']);
        $this->assertStringContains('skipped', $results['EUR']);
    }

    public function test_update_all_rates_reports_failed_when_api_returns_error(): void
    {
        $this->setApiToken();

        $usd = Currency::firstOrCreate(['code' => 'USD'], [
            'name' => 'US Dollar',
            'symbol' => '$',
            'exchange_rate' => 16.00,
            'is_active' => true,
            'is_default' => false,
        ]);
        $usd->update(['exchange_rate' => 16.00, 'is_active' => true]);

        Http::fake([
            'www.banxico.org.mx/*' => Http::response(
                ['error' => 'Service unavailable'],
                503
            ),
        ]);

        Log::shouldReceive('error')->atLeast()->once();

        $results = $this->provider->updateAllRates();

        $this->assertStringContains('failed', $results['USD']);
    }

    public function test_update_all_rates_resets_mxn_to_one(): void
    {
        $this->setApiToken();

        $mxn = Currency::firstOrCreate(['code' => 'MXN'], [
            'name' => 'Mexican Peso',
            'symbol' => '$',
            'exchange_rate' => 1.5,
            'is_active' => true,
            'is_default' => true,
        ]);
        $mxn->update(['exchange_rate' => 1.5]); // Accidentally not 1.0

        Http::fake([
            'www.banxico.org.mx/*' => Http::response(
                $this->fakeBanxicoResponse('17.54'),
                200
            ),
        ]);

        $results = $this->provider->updateAllRates();

        $mxn->refresh();
        $this->assertEquals(1.0, $mxn->exchange_rate);
        $this->assertEquals('reset to 1.0', $results['MXN']);
    }

    public function test_update_all_rates_does_not_touch_mxn_if_already_one(): void
    {
        $this->setApiToken();

        $mxn = Currency::firstOrCreate(['code' => 'MXN'], [
            'name' => 'Mexican Peso',
            'symbol' => '$',
            'exchange_rate' => 1.0,
            'is_active' => true,
            'is_default' => true,
        ]);
        $mxn->update(['exchange_rate' => 1.0]);

        Http::fake([
            'www.banxico.org.mx/*' => Http::response(
                $this->fakeBanxicoResponse('17.54'),
                200
            ),
        ]);

        $results = $this->provider->updateAllRates();

        $this->assertArrayNotHasKey('MXN', $results);
    }

    // -------------------------------------------------------
    // getSupportedCurrencies
    // -------------------------------------------------------

    public function test_get_supported_currencies_returns_usd_and_eur(): void
    {
        $supported = $this->provider->getSupportedCurrencies();

        $this->assertContains('USD', $supported);
        $this->assertContains('EUR', $supported);
        $this->assertCount(2, $supported);
    }

    // -------------------------------------------------------
    // Helper assertion
    // -------------------------------------------------------

    /**
     * Assert that a string contains a given substring.
     */
    private function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertStringContainsString($needle, $haystack);
    }
}
