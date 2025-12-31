<?php

namespace Modules\Sales\JsonApi\V1\ShipmentItems;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

/**
 * SA-M001: JSON:API Request validation for ShipmentItem.
 */
class ShipmentItemRequest extends ResourceRequest
{
    public function rules(): array
    {
        $isCreating = $this->isCreating();

        return [
            'shipmentId' => [
                $isCreating ? 'required' : 'sometimes',
                'exists:shipments,id',
            ],
            'salesOrderItemId' => [
                $isCreating ? 'required' : 'sometimes',
                'exists:sales_order_items,id',
            ],
            'quantity' => [
                $isCreating ? 'required' : 'sometimes',
                'numeric',
                'min:0.01',
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'shipmentId.required' => 'El envio es requerido.',
            'shipmentId.exists' => 'El envio seleccionado no existe.',
            'salesOrderItemId.required' => 'El item de la orden es requerido.',
            'salesOrderItemId.exists' => 'El item de la orden seleccionado no existe.',
            'quantity.required' => 'La cantidad es requerida.',
            'quantity.numeric' => 'La cantidad debe ser un numero.',
            'quantity.min' => 'La cantidad debe ser mayor a 0.',
            'notes.max' => 'Las notas no pueden exceder :max caracteres.',
        ];
    }
}
