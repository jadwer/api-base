<?php

namespace Modules\Ecommerce\JsonApi\V1\CartItems;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class CartItemRequest extends ResourceRequest
{
    public function rules(): array
    {
        $cartitem = $this->model();
        
        return [
            'shoppingCart' => ['required'],
            'product' => ['required'], 
            'quantity' => ['required', 'numeric', 'min:0'],
            'unitPrice' => ['required', 'numeric', 'min:0'],
            'originalPrice' => ['required', 'numeric', 'min:0'],
            'discountPercent' => ['required', 'numeric', 'min:0', 'max:100'],
            'discountAmount' => ['required', 'numeric', 'min:0'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'taxRate' => ['required', 'numeric', 'min:0', 'max:100'],
            'taxAmount' => ['required', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'metadata' => ['nullable', 'array'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ];
    }

    public function messages(): array
    {
        return [
            'shoppingCart.required' => 'El carrito es obligatorio.',
            'product.required' => 'El producto es obligatorio.',
            'quantity.required' => 'La cantidad es obligatoria.',
            'quantity.numeric' => 'La cantidad debe ser un número.',
            'quantity.min' => 'La cantidad debe ser mayor o igual a 0.',
            'unitPrice.required' => 'El precio unitario es obligatorio.',
            'unitPrice.numeric' => 'El precio unitario debe ser un número.',
            'unitPrice.min' => 'El precio unitario debe ser mayor o igual a 0.',
            'originalPrice.required' => 'El precio original es obligatorio.',
            'originalPrice.numeric' => 'El precio original debe ser un número.',
            'originalPrice.min' => 'El precio original debe ser mayor o igual a 0.',
            'discountPercent.required' => 'El porcentaje de descuento es obligatorio.',
            'discountPercent.numeric' => 'El porcentaje de descuento debe ser un número.',
            'discountPercent.min' => 'El porcentaje de descuento debe ser mayor o igual a 0.',
            'discountPercent.max' => 'El porcentaje de descuento no puede ser mayor a 100.',
            'discountAmount.required' => 'El importe de descuento es obligatorio.',
            'discountAmount.numeric' => 'El importe de descuento debe ser un número.',
            'discountAmount.min' => 'El importe de descuento debe ser mayor o igual a 0.',
            'subtotal.required' => 'El subtotal es obligatorio.',
            'subtotal.numeric' => 'El subtotal debe ser un número.',
            'subtotal.min' => 'El subtotal debe ser mayor o igual a 0.',
            'taxRate.required' => 'La tasa de impuesto es obligatoria.',
            'taxRate.numeric' => 'La tasa de impuesto debe ser un número.',
            'taxRate.min' => 'La tasa de impuesto debe ser mayor o igual a 0.',
            'taxRate.max' => 'La tasa de impuesto no puede ser mayor a 100.',
            'taxAmount.required' => 'Los impuestos son obligatorios.',
            'taxAmount.numeric' => 'Los impuestos deben ser un número.',
            'taxAmount.min' => 'Los impuestos deben ser mayores o iguales a 0.',
            'total.required' => 'El total es obligatorio.',
            'total.numeric' => 'El total debe ser un número.',
            'total.min' => 'El total debe ser mayor o igual a 0.',
            'status.in' => 'El estado debe ser activo o inactivo.',
        ];
    }
}
