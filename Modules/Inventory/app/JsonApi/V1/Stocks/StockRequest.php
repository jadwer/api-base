<?php

namespace Modules\Inventory\JsonApi\V1\Stocks;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;
use Modules\Inventory\Models\Stock;

class StockRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     *
     * @return array
     */
    public function rules(): array
    {
        $stock = $this->model();

        return [
            'quantity' => ['required', 'numeric', 'min:0'],
            'reservedQuantity' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'minimumStock' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'maximumStock' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'reorderPoint' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'unitCost' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'in:active,inactive,quarantine,damaged'],
            'lastMovementDate' => ['sometimes', 'nullable', 'date'],
            'lastMovementType' => ['sometimes', 'nullable', 'string', 'in:in,out,adjustment,transfer'],
            'batchInfo' => ['sometimes', 'nullable', 'array'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            // Foreign keys como atributos
            'productId' => ['required', 'integer', 'exists:products,id'],
            'warehouseId' => ['required', 'integer', 'exists:warehouses,id'],
            'locationId' => ['sometimes', 'nullable', 'integer', 'exists:warehouse_locations,id'],
        ];
    }

}
