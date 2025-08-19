<?php

namespace Modules\Accounting\JsonApi\V1\ExchangeRates;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class ExchangeRateResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'baseCurrency' => $this->base_currency,
            'quoteCurrency' => $this->quote_currency,
            'rateDate' => $this->rate_date,
            'rate' => $this->rate,
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
