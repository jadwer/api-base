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
            // Sin reglas de precio ni de totales: son de solo lectura en el Schema
            // y los calcula el servidor (un cliente no fija su propio precio).
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
            'status.in' => 'El estado debe ser activo o inactivo.',
        ];
    }
}
