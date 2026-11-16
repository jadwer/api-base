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
            // Las existencias salen de los movimientos (decision 2026-11-15): cantidad, reservado,
            // costo y ultimo movimiento no se capturan por la API.
            'quantity' => [$this->notCaptured('La existencia no se captura a mano: registra una entrada o un ajuste en Movimientos.')],
            'reservedQuantity' => [$this->notCaptured('Lo reservado lo calculan los pedidos; no se captura a mano.')],
            'minimumStock' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'maximumStock' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'reorderPoint' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'unitCost' => [$this->notCaptured('El costo sale de las entradas registradas; no se captura a mano.')],
            'status' => $isUpdate ? ['sometimes', 'string', 'in:active,inactive,quarantine,damaged'] : ['required', 'string', 'in:active,inactive,quarantine,damaged'],
            'lastMovementDate' => [$this->notCaptured('El último movimiento lo registra el sistema.')],
            'lastMovementType' => [$this->notCaptured('El último movimiento lo registra el sistema.')],
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


    /**
     * Rechaza el campo solo si el cliente lo mando. En PATCH, laravel-json-api mezcla los
     * valores guardados antes de validar: 'prohibited' fallaria siempre.
     */
    private function notCaptured(string $message): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($message): void {
            if (array_key_exists($attribute, (array) $this->input('data.attributes', []))) {
                $fail($message);
            }
        };
    }
}
