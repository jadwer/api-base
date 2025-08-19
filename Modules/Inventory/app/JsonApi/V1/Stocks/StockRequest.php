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
        $stockId = $stock ? $stock->getKey() : null;
        $isUpdate = $this->isMethod('patch');

        return [
            'quantity' => $isUpdate ? ['sometimes', 'numeric', 'min:0'] : ['required', 'numeric', 'min:0'],
            'reservedQuantity' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'minimumStock' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'maximumStock' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'reorderPoint' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'unitCost' => $isUpdate ? ['sometimes', 'numeric', 'min:0'] : ['required', 'numeric', 'min:0'],
            'status' => $isUpdate ? ['sometimes', 'string', 'in:active,inactive,quarantine,damaged'] : ['required', 'string', 'in:active,inactive,quarantine,damaged'],
            'lastMovementDate' => ['sometimes', 'nullable', 'date'],
            'lastMovementType' => ['sometimes', 'nullable', 'string', 'in:in,out,adjustment,transfer'],
            'batchInfo' => ['sometimes', 'nullable', 'array'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            // Foreign keys como atributos con validación unique
            'productId' => array_merge(
                $isUpdate ? ['sometimes'] : ['required'],
                [
                    'integer', 
                    'exists:products,id',
                    function ($attribute, $value, $fail) use ($stockId, $stock) {
                        if (!$value) return; // Skip validation if not provided in update
                        
                        $query = \Modules\Inventory\Models\Stock::where('product_id', $value)
                            ->where('warehouse_id', $this->input('warehouseId', $stock->warehouse_id ?? null))
                            ->where('warehouse_location_id', $this->input('locationId', $stock->warehouse_location_id ?? null));
                        
                        if ($stockId) {
                            $query->where('id', '!=', $stockId);
                        }
                        
                        if ($query->exists()) {
                            $fail('A stock entry already exists for this product in this warehouse and location.');
                        }
                    }
                ]
            ),
            'warehouseId' => $isUpdate ? ['sometimes', 'integer', 'exists:warehouses,id'] : ['required', 'integer', 'exists:warehouses,id'],
            'locationId' => ['sometimes', 'nullable', 'integer', 'exists:warehouse_locations,id'],
            // Relationships
            'product' => $isUpdate ? JsonApiRule::toOne() : [JsonApiRule::toOne(), 'required'],
            'warehouse' => $isUpdate ? JsonApiRule::toOne() : [JsonApiRule::toOne(), 'required'],
            'location' => JsonApiRule::toOne(),
        ];
    }

}
