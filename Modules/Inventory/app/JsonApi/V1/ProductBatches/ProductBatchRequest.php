<?php

namespace Modules\Inventory\JsonApi\V1\ProductBatches;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class ProductBatchRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'batchNumber' => ['required', 'string', 'max:255'],
            'lotNumber' => ['sometimes', 'nullable', 'string', 'max:255'],
            'manufacturingDate' => ['sometimes', 'nullable', 'date'],
            'expirationDate' => ['sometimes', 'nullable', 'date', 'after_or_equal:manufacturing_date'],
            'bestBeforeDate' => ['sometimes', 'nullable', 'date', 'after_or_equal:manufacturing_date'],
            'initialQuantity' => ['required', 'numeric', 'min:0'],
            'currentQuantity' => ['required', 'numeric', 'min:0'],
            'reservedQuantity' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'unitCost' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'in:active,expired,quarantine,recalled,consumed'],
            'supplierName' => ['sometimes', 'nullable', 'string', 'max:255'],
            'supplierBatch' => ['sometimes', 'nullable', 'string', 'max:255'],
            'qualityNotes' => ['sometimes', 'nullable', 'string'],
            'testResults' => ['sometimes', 'nullable', 'array'],
            'certifications' => ['sometimes', 'nullable', 'array'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'product' => JsonApiRule::toOne(),
            'warehouse' => JsonApiRule::toOne(),
            'warehouseLocation' => ['sometimes', JsonApiRule::toOne()],
        ];
    }
}
