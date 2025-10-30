<?php

namespace Modules\Ecommerce\JsonApi\V1\Currencies;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class CurrencyResource extends JsonApiResource
{
    /**
     * Get the resource's attributes.
     *
     * @param \Illuminate\Http\Request|null $request
     * @return array
     */
    public function attributes($request): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'symbol' => $this->symbol,
            'exchangeRate' => $this->exchange_rate,
            'isActive' => $this->is_active,
            'isDefault' => $this->is_default,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }

    /**
     * Get the resource's relationships.
     *
     * @param \Illuminate\Http\Request|null $request
     * @return array
     */
    public function relationships($request): array
    {
        return [];
    }
}
