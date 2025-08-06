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
            'sessionId.string' => 'Session ID must be a string.',
            'sessionId.max' => 'Session ID cannot exceed 255 characters.',
            'status.required' => 'Status is required.',
            'status.in' => 'Status must be active, inactive, or expired.',
            'expiresAt.date' => 'Expires at must be a valid date.',
            'totalAmount.required' => 'Total amount is required.',
            'totalAmount.numeric' => 'Total amount must be a number.',
            'totalAmount.min' => 'Total amount must be at least 0.',
            'currency.required' => 'Currency is required.',
            'currency.max' => 'Currency code cannot exceed 3 characters.',
            'couponCode.max' => 'Coupon code cannot exceed 255 characters.',
            'discountAmount.numeric' => 'Discount amount must be a number.',
            'discountAmount.min' => 'Discount amount must be at least 0.',
            'taxAmount.numeric' => 'Tax amount must be a number.',
            'taxAmount.min' => 'Tax amount must be at least 0.',
            'shippingAmount.numeric' => 'Shipping amount must be a number.',
            'shippingAmount.min' => 'Shipping amount must be at least 0.',
        ];
    }
}
