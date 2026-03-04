<?php

namespace App\Services;

use Carbon\Carbon;
use Modules\AppConfig\Models\AppSetting;
use Modules\Ecommerce\Models\Currency;

class CurrencyConversionService
{
    /**
     * Convert an amount from one currency to another.
     *
     * Exchange rates in the currencies table represent how many units of the
     * BASE currency (MXN) equal 1 unit of that currency.
     * Example: USD exchange_rate=17.5 means 1 USD = 17.5 MXN
     *
     * Formula: convertedAmount = amount * fromRate / toRate
     */
    public function convert(float $amount, string $fromCode, string $toCode): ConversionResult
    {
        if ($fromCode === $toCode) {
            return new ConversionResult(
                originalAmount: $amount,
                convertedAmount: $amount,
                fromCurrency: $fromCode,
                toCurrency: $toCode,
                exchangeRate: 1.0,
                marginApplied: 0.0,
                effectiveRate: 1.0,
                rateDate: Carbon::now(),
            );
        }

        $fromCurrency = Currency::where('code', $fromCode)->where('is_active', true)->first();
        $toCurrency = Currency::where('code', $toCode)->where('is_active', true)->first();

        if (!$fromCurrency) {
            throw new \InvalidArgumentException("Currency not found or inactive: {$fromCode}");
        }

        if (!$toCurrency) {
            throw new \InvalidArgumentException("Currency not found or inactive: {$toCode}");
        }

        if ($fromCurrency->exchange_rate == 0) {
            throw new \DivisionByZeroError("Exchange rate is zero for currency: {$fromCode}");
        }

        if ($toCurrency->exchange_rate == 0) {
            throw new \DivisionByZeroError("Exchange rate is zero for currency: {$toCode}");
        }

        $rawRate = $fromCurrency->exchange_rate / $toCurrency->exchange_rate;
        $margin = $this->getMarginPercentage();
        $effectiveRate = $rawRate * (1 + ($margin / 100));
        $convertedAmount = round($amount * $effectiveRate, 2);

        return new ConversionResult(
            originalAmount: $amount,
            convertedAmount: $convertedAmount,
            fromCurrency: $fromCode,
            toCurrency: $toCode,
            exchangeRate: round($rawRate, 6),
            marginApplied: $margin,
            effectiveRate: round($effectiveRate, 6),
            rateDate: Carbon::now(),
        );
    }

    /**
     * Get the raw exchange rate between two currencies (no margin).
     */
    public function getExchangeRate(string $fromCode, string $toCode): float
    {
        if ($fromCode === $toCode) {
            return 1.0;
        }

        $from = Currency::where('code', $fromCode)->where('is_active', true)->firstOrFail();
        $to = Currency::where('code', $toCode)->where('is_active', true)->firstOrFail();

        return $from->exchange_rate / $to->exchange_rate;
    }

    /**
     * Get the effective exchange rate (with margin applied).
     */
    public function getEffectiveRate(string $fromCode, string $toCode): float
    {
        $rawRate = $this->getExchangeRate($fromCode, $toCode);
        $margin = $this->getMarginPercentage();

        return $rawRate * (1 + ($margin / 100));
    }

    /**
     * Get the configured margin percentage from AppConfig.
     */
    public function getMarginPercentage(): float
    {
        return (float) AppSetting::get('currency.exchange_rate_margin', 0);
    }

    /**
     * Get the base currency code from AppConfig.
     */
    public function getBaseCurrencyCode(): string
    {
        return AppSetting::get('currency.base_currency', 'MXN');
    }
}
