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
            'quantity' => [$creating ? 'required' : 'sometimes', 'numeric', 'min:0.01'],
            'unitPrice' => [$creating ? 'required' : 'sometimes', 'numeric', 'min:0', function (string $attribute, mixed $value, \Closure $fail) {
                $metadata = $this->validationData()['metadata'] ?? [];
                if ((float) $value <= 0 && empty($metadata['no_charge'])) {
                    $fail(\Modules\Purchase\Support\NoChargeItem::MESSAGE);
                }
            }],
            'discount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'subtotal' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'total' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'invoicedQuantity' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'invoicedAmount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'metadata' => ['sometimes', 'nullable', 'array'],

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
            'purchaseOrderId.required' => 'La orden de compra es obligatoria.',
            'purchaseOrderId.exists' => 'La orden de compra seleccionada no existe.',
            'productId.required' => 'El producto es obligatorio.',
            'productId.exists' => 'El producto seleccionado no existe.',
            'quantity.required' => 'La cantidad es obligatoria.',
            'quantity.numeric' => 'La cantidad debe ser un número válido.',
            'quantity.min' => 'La cantidad debe ser mayor a 0.',
            'unitPrice.required' => 'El precio unitario es obligatorio.',
            'unitPrice.numeric' => 'El precio unitario debe ser un número válido.',
            'unitPrice.min' => 'El precio unitario debe ser mayor o igual a 0.',
            'discount.numeric' => 'El descuento debe ser un número válido.',
            'discount.min' => 'El descuento debe ser mayor o igual a 0.',
            'subtotal.numeric' => 'El subtotal debe ser un número válido.',
            'subtotal.min' => 'El subtotal debe ser mayor o igual a 0.',
            'total.numeric' => 'El total debe ser un número válido.',
            'total.min' => 'El total debe ser mayor o igual a 0.',
        ];
    }
}
