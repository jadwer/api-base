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
        $creating = $this->isCreating();
        
        return [
            // Campos obligatorios
            'purchaseOrderId' => [$creating ? 'required' : 'sometimes', 'exists:purchase_orders,id'],
            'productId' => [$creating ? 'required' : 'sometimes', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unitPrice' => ['required', 'numeric', 'min:0'],
            'discount' => ['sometimes', 'numeric', 'min:0'],
            'receivedQuantity' => ['sometimes', 'numeric', 'min:0'],
            'subtotal' => ['sometimes', 'numeric', 'min:0'],
            'total' => ['sometimes', 'numeric', 'min:0'],
            'metadata' => ['sometimes', 'array'],

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
            'purchaseOrderId.required' => 'Purchase Order ID is required.',
            'purchaseOrderId.exists' => 'The selected purchase order does not exist.',
            'productId.required' => 'Product ID is required.',
            'productId.exists' => 'The selected product does not exist.',
            'quantity.required' => 'La cantidad es obligatoria.',
            'quantity.numeric' => 'La cantidad debe ser un número válido.',
            'quantity.min' => 'La cantidad debe ser mayor a 0.',
            'unitPrice.required' => 'El precio unitario es obligatorio.',
            'unitPrice.numeric' => 'El precio unitario debe ser un número válido.',
            'unitPrice.min' => 'El precio unitario debe ser mayor o igual a 0.',
            'discount.numeric' => 'El descuento debe ser un número válido.',
            'discount.min' => 'El descuento debe ser mayor o igual a 0.',
            'receivedQuantity.numeric' => 'La cantidad recibida debe ser un número válido.',
            'receivedQuantity.min' => 'La cantidad recibida debe ser mayor o igual a 0.',
            'subtotal.numeric' => 'El subtotal debe ser un número válido.',
            'subtotal.min' => 'El subtotal debe ser mayor o igual a 0.',
            'total.numeric' => 'El total debe ser un número válido.',
            'total.min' => 'El total debe ser mayor o igual a 0.',
        ];
    }
}
