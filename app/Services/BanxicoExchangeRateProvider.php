<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\AppConfig\Models\AppSetting;
use Modules\Ecommerce\Models\Currency;

class BanxicoExchangeRateProvider
{
    /**
     * Banxico SIE API base URL
     */
    private const BASE_URL = 'https://www.banxico.org.mx/SieAPIRest/service/v1/series';

    /**
     * Series IDs for exchange rates (pesos per 1 unit of foreign currency)
     * SF43718 = USD/MXN FIX (Diario Oficial de la Federación)
     * SF46410 = EUR/MXN
     */
    private const SERIES = [
        'USD' => 'SF43718',
        'EUR' => 'SF46410',
    ];

    /**
     * Fetch the latest USD/MXN rate from Banxico.
     */
    public function fetchUSDRate(): ?float
    {
        return $this->fetchRate('USD');
    }

    /**
     * Fetch the latest EUR/MXN rate from Banxico.
     */
    public function fetchEURRate(): ?float
    {
        return $this->fetchRate('EUR');
    }

    /**
     * Fetch rate for a specific currency from Banxico.
     */
    public function fetchRate(string $currencyCode): ?float
    {
        $seriesId = self::SERIES[$currencyCode] ?? null;
        if (!$seriesId) {
            Log::warning("BanxicoProvider: No series ID for currency {$currencyCode}");
            return null;
        }

        $token = AppSetting::get('currency.banxico_api_token', '');
        if (empty($token)) {
            Log::warning('BanxicoProvider: No API token configured (currency.banxico_api_token)');
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Bmx-Token' => $token,
            ])->timeout(10)->get(self::BASE_URL . "/{$seriesId}/datos/oportuno");

            if (!$response->successful()) {
                Log::error("BanxicoProvider: HTTP {$response->status()} for {$currencyCode}");
                return null;
            }

            $data = $response->json();
            $series = $data['bmx']['series'][0]['datos'] ?? [];

            if (empty($series)) {
                Log::warning("BanxicoProvider: No data returned for {$currencyCode}");
                return null;
            }

            $latestEntry = end($series);
            $rate = str_replace(',', '', $latestEntry['dato'] ?? '0');

            if (!is_numeric($rate) || (float) $rate <= 0) {
                Log::error("BanxicoProvider: Invalid rate value '{$rate}' for {$currencyCode}");
                return null;
            }

            return (float) $rate;
        } catch (\Exception $e) {
            Log::error("BanxicoProvider: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Update all currency exchange rates from Banxico.
     * Only updates currencies that have a Banxico series mapping.
     * MXN is always 1.0 (base currency).
     *
     * @return array Summary of updates performed
     */
    public function updateAllRates(): array
    {
        $results = [];

        foreach (self::SERIES as $code => $seriesId) {
            $currency = Currency::where('code', $code)->where('is_active', true)->first();
            if (!$currency) {
                $results[$code] = 'skipped (not found or inactive)';
                continue;
            }

            $rate = $this->fetchRate($code);
            if ($rate === null) {
                $results[$code] = 'failed (API error)';
                continue;
            }

            $oldRate = $currency->exchange_rate;
            $currency->update(['exchange_rate' => $rate]);
            $results[$code] = "updated: {$oldRate} → {$rate}";
        }

        // MXN is always 1.0
        $mxn = Currency::where('code', 'MXN')->first();
        if ($mxn && $mxn->exchange_rate != 1.0) {
            $mxn->update(['exchange_rate' => 1.0]);
            $results['MXN'] = 'reset to 1.0';
        }

        return $results;
    }

    /**
     * Get the list of supported currency codes.
     */
    public function getSupportedCurrencies(): array
    {
        return array_keys(self::SERIES);
    }
}
