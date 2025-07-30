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
            'session_id' => ['nullable', 'string', 'max:255'],
            'user_id' => ['nullable', 'string'],
            'status' => ['required', 'string', 'max:255'],
            'expires_at' => ['nullable', 'string'],
            'total_amount' => ['required', 'string'],
            'currency' => ['required', 'string', 'max:255'],
            'coupon_code' => ['nullable', 'string', 'max:255'],
            'discount_amount' => ['required', 'string'],
            'tax_amount' => ['required', 'string'],
            'shipping_amount' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'session_id.string' => 'El campo Session id debe ser texto.',
            'session_id.max' => 'El campo Session id no puede tener más de 255 caracteres.',
            'status.required' => 'El campo Status es obligatorio.',
            'status.string' => 'El campo Status debe ser texto.',
            'status.max' => 'El campo Status no puede tener más de 255 caracteres.',
            'total_amount.required' => 'El campo Total amount es obligatorio.',
            'currency.required' => 'El campo Currency es obligatorio.',
            'currency.string' => 'El campo Currency debe ser texto.',
            'currency.max' => 'El campo Currency no puede tener más de 255 caracteres.',
            'coupon_code.string' => 'El campo Coupon code debe ser texto.',
            'coupon_code.max' => 'El campo Coupon code no puede tener más de 255 caracteres.',
            'discount_amount.required' => 'El campo Discount amount es obligatorio.',
            'tax_amount.required' => 'El campo Tax amount es obligatorio.',
            'shipping_amount.required' => 'El campo Shipping amount es obligatorio.',
            'notes.string' => 'El campo Notes debe ser texto.',
        ];
    }
}
