<?php

namespace Modules\Inventory\JsonApi\V1\Stocks;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class StockRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'quantity' => ['required', 'numeric', 'min:0'],
            'reservedQuantity' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'minimumStock' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'maximumStock' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'reorderPoint' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'unitCost' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'in:active,inactive,blocked,depleted'],
            'lastMovementDate' => ['sometimes', 'nullable', 'date'],
            'lastMovementType' => ['sometimes', 'nullable', 'string', 'in:in,out,adjustment,transfer'],
            'batchInfo' => ['sometimes', 'nullable', 'array'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'product' => JsonApiRule::toOne(),
            'warehouse' => JsonApiRule::toOne(),
            'location' => ['sometimes', JsonApiRule::toOne()],
        ];
    }
}
