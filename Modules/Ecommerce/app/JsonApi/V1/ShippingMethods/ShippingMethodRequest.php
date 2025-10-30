<?php

namespace Modules\Ecommerce\JsonApi\V1\ShippingMethods;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class ShippingMethodRequest extends ResourceRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                $this->isCreating() ? 'required' : 'sometimes',
                'string',
                'max:255',
            ],
            'code' => [
                $this->isCreating() ? 'required' : 'sometimes',
                'string',
                'max:50',
                $this->unique('shipping_methods'),
            ],
            'description' => [
                'sometimes',
                'nullable',
                'string',
                'max:1000',
            ],
            'carrier' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'baseCost' => [
                $this->isCreating() ? 'required' : 'sometimes',
                'numeric',
                'min:0',
            ],
            'costPerKg' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
            ],
            'costPerKm' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
            ],
            'freeShippingThreshold' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
            ],
            'estimatedDaysMin' => [
                'sometimes',
                'nullable',
                'integer',
                'min:1',
            ],
            'estimatedDaysMax' => [
                'sometimes',
                'nullable',
                'integer',
                'min:1',
            ],
            'isActive' => [
                'sometimes',
                'boolean',
            ],
            'sortOrder' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
            ],
            'availableRegions' => [
                'sometimes',
                'nullable',
                'array',
            ],
            'metadata' => [
                'sometimes',
                'nullable',
                'array',
            ],
        ];
    }
}
