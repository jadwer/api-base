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
            'shopping_cart_id' => ['required', 'string'],
            'product_id' => ['required', 'string'],
            'quantity' => ['required', 'string'],
            'unit_price' => ['required', 'string'],
            'original_price' => ['required', 'string'],
            'discount_percent' => ['required', 'string'],
            'discount_amount' => ['required', 'string'],
            'subtotal' => ['required', 'string'],
            'tax_rate' => ['required', 'string'],
            'tax_amount' => ['required', 'string'],
            'total' => ['required', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'shopping_cart_id.required' => 'El campo Shopping cart id es obligatorio.',
            'product_id.required' => 'El campo Product id es obligatorio.',
            'quantity.required' => 'El campo Quantity es obligatorio.',
            'unit_price.required' => 'El campo Unit price es obligatorio.',
            'original_price.required' => 'El campo Original price es obligatorio.',
            'discount_percent.required' => 'El campo Discount percent es obligatorio.',
            'discount_amount.required' => 'El campo Discount amount es obligatorio.',
            'subtotal.required' => 'El campo Subtotal es obligatorio.',
            'tax_rate.required' => 'El campo Tax rate es obligatorio.',
            'tax_amount.required' => 'El campo Tax amount es obligatorio.',
            'total.required' => 'El campo Total es obligatorio.',
        ];
    }
}
