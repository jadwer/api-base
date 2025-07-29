<?php

namespace Modules\Sales\JsonApi\V1\SalesOrderItems;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class SalesOrderItemRequest extends ResourceRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'salesOrderId' => ['required', 'integer', 'exists:sales_orders,id'],
            'productId' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unitPrice' => ['required', 'numeric', 'min:0'],
            'discount' => ['sometimes', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'salesOrderId.required' => 'La orden de venta es obligatoria.',
            'salesOrderId.exists' => 'La orden de venta no existe.',
            'productId.required' => 'El producto es obligatorio.',
            'productId.exists' => 'El producto no existe.',
            'quantity.required' => 'La cantidad es obligatoria.',
            'quantity.min' => 'La cantidad debe ser mayor a 0.',
            'unitPrice.required' => 'El precio unitario es obligatorio.',
            'unitPrice.min' => 'El precio unitario debe ser mayor o igual a 0.',
            'total.required' => 'El total es obligatorio.',
            'total.min' => 'El total debe ser mayor o igual a 0.',
            'discount.min' => 'El descuento debe ser mayor o igual a 0.',
            'metadata.array' => 'Los metadatos deben ser un objeto.',
        ];
    }

    /**
     * Get default values for the request.
     */
    public function withDefaults(): array
    {
        return [
            'discount' => 0.0,
            'metadata' => [],
        ];
    }
}
