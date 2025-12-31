<?php

namespace Modules\Sales\JsonApi\V1\Backorders;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

/**
 * SA-M002: JSON:API Request validation for Backorder.
 */
class BackorderRequest extends ResourceRequest
{
    public function rules(): array
    {
        $model = $this->model();
        $isCreating = $this->isCreating();

        return [
            'salesOrderId' => [
                $isCreating ? 'required' : 'sometimes',
                'exists:sales_orders,id',
            ],
            'salesOrderItemId' => [
                $isCreating ? 'required' : 'sometimes',
                'exists:sales_order_items,id',
            ],
            'productId' => [
                $isCreating ? 'required' : 'sometimes',
                'exists:products,id',
            ],
            'warehouseId' => ['nullable', 'exists:warehouses,id'],
            'backorderNumber' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('backorders', 'backorder_number')->ignore($model),
            ],
            'originalQuantity' => ['sometimes', 'numeric', 'min:0'],
            'backorderQuantity' => [
                $isCreating ? 'required' : 'sometimes',
                'numeric',
                'min:0.01',
            ],
            'fulfilledQuantity' => ['sometimes', 'numeric', 'min:0'],
            'status' => [
                'sometimes',
                Rule::in(['pending', 'partially_fulfilled', 'fulfilled', 'cancelled']),
            ],
            'priority' => [
                'sometimes',
                Rule::in(['low', 'normal', 'high', 'urgent']),
            ],
            'expectedDate' => ['nullable', 'date'],
            'promisedDate' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'customerNotes' => ['nullable', 'string', 'max:500'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'salesOrderId.required' => 'La orden de venta es requerida.',
            'salesOrderId.exists' => 'La orden de venta no existe.',
            'salesOrderItemId.required' => 'El item de la orden es requerido.',
            'salesOrderItemId.exists' => 'El item de la orden no existe.',
            'productId.required' => 'El producto es requerido.',
            'productId.exists' => 'El producto no existe.',
            'warehouseId.exists' => 'El almacen no existe.',
            'backorderNumber.unique' => 'Este numero de backorder ya existe.',
            'backorderQuantity.required' => 'La cantidad de backorder es requerida.',
            'backorderQuantity.min' => 'La cantidad debe ser mayor a 0.',
            'status.in' => 'Estado de backorder invalido.',
            'priority.in' => 'Prioridad invalida.',
            'expectedDate.date' => 'La fecha esperada debe ser una fecha valida.',
            'promisedDate.date' => 'La fecha prometida debe ser una fecha valida.',
            'notes.max' => 'Las notas no pueden exceder :max caracteres.',
            'customerNotes.max' => 'Las notas del cliente no pueden exceder :max caracteres.',
        ];
    }
}
