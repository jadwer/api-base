<?php

namespace App\Services;

use Carbon\Carbon;

class ConversionResult
{
    public function __construct(
        public readonly float $originalAmount,
        public readonly float $convertedAmount,
        public readonly string $fromCurrency,
        public readonly string $toCurrency,
        public readonly float $exchangeRate,
        public readonly float $marginApplied,
        public readonly float $effectiveRate,
        public readonly Carbon $rateDate,
    ) {}

    public function toArray(): array
    {
        return [
            'original_amount' => $this->originalAmount,
            'converted_amount' => $this->convertedAmount,
            'from_currency' => $this->fromCurrency,
            'to_currency' => $this->toCurrency,
            'exchange_rate' => $this->exchangeRate,
            'margin_applied' => $this->marginApplied,
            'effective_rate' => $this->effectiveRate,
            'rate_date' => $this->rateDate->toDateString(),
        ];
    }
}
