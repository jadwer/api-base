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
            'shoppingCart.required' => 'Shopping cart is required.',
            'product.required' => 'Product is required.',
            'quantity.required' => 'Quantity is required.',
            'quantity.numeric' => 'Quantity must be a number.',
            'quantity.min' => 'Quantity must be at least 0.',
            'unitPrice.required' => 'Unit price is required.',
            'unitPrice.numeric' => 'Unit price must be a number.',
            'unitPrice.min' => 'Unit price must be at least 0.',
            'originalPrice.required' => 'Original price is required.',
            'originalPrice.numeric' => 'Original price must be a number.',
            'originalPrice.min' => 'Original price must be at least 0.',
            'discountPercent.required' => 'Discount percent is required.',
            'discountPercent.numeric' => 'Discount percent must be a number.',
            'discountPercent.min' => 'Discount percent must be at least 0.',
            'discountPercent.max' => 'Discount percent must be at most 100.',
            'discountAmount.required' => 'Discount amount is required.',
            'discountAmount.numeric' => 'Discount amount must be a number.',
            'discountAmount.min' => 'Discount amount must be at least 0.',
            'subtotal.required' => 'Subtotal is required.',
            'subtotal.numeric' => 'Subtotal must be a number.',
            'subtotal.min' => 'Subtotal must be at least 0.',
            'taxRate.required' => 'Tax rate is required.',
            'taxRate.numeric' => 'Tax rate must be a number.',
            'taxRate.min' => 'Tax rate must be at least 0.',
            'taxRate.max' => 'Tax rate must be at most 100.',
            'taxAmount.required' => 'Tax amount is required.',
            'taxAmount.numeric' => 'Tax amount must be a number.',
            'taxAmount.min' => 'Tax amount must be at least 0.',
            'total.required' => 'Total is required.',
            'total.numeric' => 'Total must be a number.',
            'total.min' => 'Total must be at least 0.',
            'status.in' => 'Status must be either active or inactive.',
        ];
    }
}
