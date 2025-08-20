<?php

namespace Modules\Ecommerce\JsonApi\V1\ShoppingCarts;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class ShoppingCartRequest extends ResourceRequest
{
    public function rules(): array
    {
        $shoppingcart = $this->model();
        
        return [
            'sessionId' => ['nullable', 'string', 'max:255'],
            'user' => ['nullable'],
            'status' => ['required', 'string', 'in:active,inactive,expired'],
            'expiresAt' => ['nullable', 'date'],
            'totalAmount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:3'],
            'couponCode' => ['nullable', 'string', 'max:255'],
            'discountAmount' => ['nullable', 'numeric', 'min:0'],
            'taxAmount' => ['nullable', 'numeric', 'min:0'],
            'shippingAmount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'sessionId.string' => 'El campo ID de sesión debe ser texto.',
            'sessionId.max' => 'El campo ID de sesión no puede tener más de 255 caracteres.',
            'status.required' => 'El campo Estado es obligatorio.',
            'status.in' => 'El campo Estado debe ser: activo, inactivo o expirado.',
            'expiresAt.date' => 'El campo Fecha de expiración debe ser una fecha válida.',
            'totalAmount.required' => 'El campo Total es obligatorio.',
            'totalAmount.numeric' => 'El campo Total debe ser un número.',
            'totalAmount.min' => 'El campo Total debe ser al menos 0.',
            'currency.required' => 'El campo Moneda es obligatorio.',
            'currency.max' => 'El campo Moneda no puede tener más de 3 caracteres.',
            'couponCode.max' => 'El campo Código de cupón no puede tener más de 255 caracteres.',
            'discountAmount.numeric' => 'El campo Descuento debe ser un número.',
            'discountAmount.min' => 'El campo Descuento debe ser al menos 0.',
            'taxAmount.numeric' => 'El campo Impuestos debe ser un número.',
            'taxAmount.min' => 'El campo Impuestos debe ser al menos 0.',
            'shippingAmount.numeric' => 'El campo Envío debe ser un número.',
            'shippingAmount.min' => 'El campo Envío debe ser al menos 0.',
        ];
    }
}
