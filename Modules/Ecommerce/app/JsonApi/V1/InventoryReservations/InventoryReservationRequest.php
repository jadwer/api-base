<?php

namespace Modules\Ecommerce\JsonApi\V1\InventoryReservations;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class InventoryReservationRequest extends ResourceRequest
{
    public function rules(): array
    {
        return [
            'checkoutSessionId' => [
                $this->isCreating() ? 'required' : 'sometimes',
                'integer',
                Rule::exists('checkout_sessions', 'id'),
            ],
            'productId' => [
                $this->isCreating() ? 'required' : 'sometimes',
                'integer',
                Rule::exists('products', 'id'),
            ],
            'warehouseId' => [
                $this->isCreating() ? 'required' : 'sometimes',
                'integer',
                Rule::exists('warehouses', 'id'),
            ],
            'productBatchId' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('product_batches', 'id'),
            ],
            'quantity' => [
                $this->isCreating() ? 'required' : 'sometimes',
                'integer',
                'min:1',
            ],
            'status' => [
                'sometimes',
                'string',
                Rule::in(['active', 'committed', 'released', 'expired']),
            ],
            'metadata' => [
                'sometimes',
                'nullable',
                'array',
            ],
        ];
    }
}
