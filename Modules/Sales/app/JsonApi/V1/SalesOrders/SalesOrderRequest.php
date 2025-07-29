<?php

namespace Modules\Sales\JsonApi\V1\SalesOrders;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class SalesOrderRequest extends ResourceRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $order = $this->model();
        
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'order_number' => [
                'required', 
                'string', 
                'max:50', 
                Rule::unique('sales_orders', 'order_number')->ignore($order?->id)
            ],
            'status' => [
                'required', 
                Rule::in(['draft', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'])
            ],
            'order_date' => ['required', 'date'],
            'approved_at' => ['nullable', 'date'],
            'delivered_at' => ['nullable', 'date'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'discount_total' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'customer_id.required' => 'Customer is required.',
            'customer_id.exists' => 'The selected customer does not exist.',
            'order_number.required' => 'Order number is required.',
            'order_number.unique' => 'This order number is already taken.',
            'status.required' => 'Order status is required.',
            'status.in' => 'Invalid order status.',
            'order_date.required' => 'Order date is required.',
            'order_date.date' => 'Order date must be a valid date.',
            'total_amount.required' => 'Total amount is required.',
            'total_amount.numeric' => 'Total amount must be a number.',
            'total_amount.min' => 'Total amount must be at least 0.',
            'discount_total.numeric' => 'Discount total must be a number.',
            'discount_total.min' => 'Discount total must be at least 0.',
            'notes.max' => 'Notes cannot exceed 1000 characters.',
        ];
    }

    /**
     * Rules for delete operations.
     */
    public function deleteRules(): array
    {
        return [];
    }
}
