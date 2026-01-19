<?php

namespace Modules\Sales\JsonApi\V1\RemissionItems;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

/**
 * SA-M006: RemissionItem Request validation
 */
class RemissionItemRequest extends ResourceRequest
{
    /**
     * Get the validation rules for creating a new resource.
     */
    public function rules(): array
    {
        return [
            'remissionId' => ['required', 'integer', 'exists:remissions,id'],
            'salesOrderItemId' => ['required', 'integer', 'exists:sales_order_items,id'],
            'productId' => ['nullable', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'min:0.0001'],
            'productName' => ['nullable', 'string', 'max:255'],
            'productSku' => ['nullable', 'string', 'max:100'],
            'unit' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:500'],
            'batchNumber' => ['nullable', 'string', 'max:100'],
            'expiryDate' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * Get the validation rules for updating the resource.
     */
    public function updateRules(): array
    {
        return [
            'quantity' => ['sometimes', 'numeric', 'min:0.0001'],
            'productName' => ['nullable', 'string', 'max:255'],
            'productSku' => ['nullable', 'string', 'max:100'],
            'unit' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:500'],
            'batchNumber' => ['nullable', 'string', 'max:100'],
            'expiryDate' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
