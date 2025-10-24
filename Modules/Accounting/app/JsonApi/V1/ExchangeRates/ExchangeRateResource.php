<?php

namespace Modules\Accounting\JsonApi\V1\ExchangeRates;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class ExchangeRateResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'fromCurrency' => $this->from_currency,
            'toCurrency' => $this->to_currency,
            'rate' => $this->rate,
            'effectiveDate' => $this->effective_date,
            'source' => $this->source,
            'status' => $this->status,
            'metadata' => $this->metadata,
            "createdAt" => $this->created_at,
            "updatedAt" => $this->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [

        ];
    }
}
