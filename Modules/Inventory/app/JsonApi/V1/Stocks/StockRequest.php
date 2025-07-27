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
            'product' => [
                'required', 
                JsonApiRule::toOne(),
                function ($attribute, $value, $fail) use ($stock) {
                    // Solo validar para creación
                    if ($stock && $stock->exists) {
                        return;
                    }
                    
                    $this->validateUniqueConstraint($value, $fail);
                }
            ],
            'warehouse' => ['required', JsonApiRule::toOne()],
            'location' => ['sometimes', JsonApiRule::toOne()],
        ];
    }

    /**
     * Validar la restricción única de stock
     */
    protected function validateUniqueConstraint($productValue, $fail): void
    {
        // Obtener los datos que Laravel ya ha parseado
        $request = request();
        $data = $request->input('data', []);
        $relationships = $data['relationships'] ?? [];

        $productId = $relationships['product']['data']['id'] ?? null;
        $warehouseId = $relationships['warehouse']['data']['id'] ?? null;
        $locationId = $relationships['location']['data']['id'] ?? null;

        if ($productId && $warehouseId) {
            $query = Stock::where('product_id', $productId)
                         ->where('warehouse_id', $warehouseId);
            
            if ($locationId) {
                $query->where('warehouse_location_id', $locationId);
            } else {
                $query->whereNull('warehouse_location_id');
            }

            if ($query->exists()) {
                $fail('Ya existe un stock para este producto en esta ubicación de bodega.');
            }
        }
    }
}
