<?php

namespace Modules\Purchase\JsonApi\V1\PurchaseOrderItems;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class PurchaseOrderItemRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unitPrice' => ['required', 'numeric', 'min:0'],
            
            // Validaciones para relaciones
            'purchaseOrder' => JsonApiRule::toOne(),
            'product' => JsonApiRule::toOne(),
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'quantity.required' => 'La cantidad es obligatoria.',
            'quantity.numeric' => 'La cantidad debe ser un número válido.',
            'quantity.min' => 'La cantidad debe ser mayor a 0.',
            'unitPrice.required' => 'El precio unitario es obligatorio.',
            'unitPrice.numeric' => 'El precio unitario debe ser un número válido.',
            'unitPrice.min' => 'El precio unitario debe ser mayor o igual a 0.',
        ];
    }
}
