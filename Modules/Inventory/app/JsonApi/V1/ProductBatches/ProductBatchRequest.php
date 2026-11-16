<?php

namespace Modules\Inventory\JsonApi\V1\ProductBatches;

use Illuminate\Validation\Rule;
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
        $productBatch = $this->model();
        $isUpdate = $this->isMethod('patch');

        return [
            'batchNumber' => [
                'required', 
                'string', 
                'max:255',
                Rule::unique('product_batches', 'batch_number')->ignore($productBatch),
            ],
            'lotNumber' => ['sometimes', 'nullable', 'string', 'max:255'],
            'manufacturingDate' => ['sometimes', 'nullable', 'date'],
            'expirationDate' => ['sometimes', 'nullable', 'date', 'after_or_equal:manufacturingDate'],
            'bestBeforeDate' => ['sometimes', 'nullable', 'date', 'after_or_equal:manufacturingDate'],
            // Las cantidades del lote salen de los movimientos (decision 2026-11-16). Al crear,
            // initialQuantity es lo que entra y se registra como una entrada ligada al lote.
            'initialQuantity' => $isUpdate
                ? [$this->notCaptured('La cantidad del lote no se captura a mano: registra una entrada o un ajuste del lote en Movimientos.')]
                : ['sometimes', 'nullable', 'numeric', 'min:0'],
            'currentQuantity' => [$this->notCaptured('La cantidad del lote no se captura a mano: registra una entrada o un ajuste del lote en Movimientos.')],
            'reservedQuantity' => [$this->notCaptured('Lo reservado del lote lo calculan los pedidos; no se captura a mano.')],
            'unitCost' => $isUpdate
                ? [$this->notCaptured('El costo del lote sale de su entrada; no se captura a mano.')]
                : ['sometimes', 'nullable', 'numeric', 'min:0'],
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

    public function withDefaults(): array
    {
        return [
            'status' => 'active',
            'reservedQuantity' => 0,
            'unitCost' => 0,
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
