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
        $isCreating = $this->isCreating();
        
        return [
            'contactId' => $isCreating
                ? ['required', 'exists:contacts,id']
                : ['sometimes', 'exists:contacts,id'],
            'orderNumber' => [
                $isCreating ? 'required' : 'sometimes',
                'string',
                'max:50',
                Rule::unique('sales_orders', 'order_number')->ignore($order?->id)
            ],
            'status' => [
                $isCreating ? 'required' : 'sometimes',
                Rule::in(['draft', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'])
            ],
            'orderDate' => [$isCreating ? 'required' : 'sometimes', 'date'],
            'approvedAt' => ['nullable', 'date'],
            'deliveredAt' => ['nullable', 'date'],
            'discountTotal' => ['nullable', 'numeric', 'min:0'],
            'totalAmount' => [$isCreating ? 'required' : 'sometimes', 'numeric', 'min:0'],
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
            'contactId.required' => 'Contact is required.',
            'contactId.exists' => 'The selected contact does not exist.',
            'orderNumber.required' => 'Order number is required.',
            'orderNumber.unique' => 'This order number is already taken.',
            'status.required' => 'Order status is required.',
            'status.in' => 'Invalid order status.',
            'orderDate.required' => 'Order date is required.',
            'orderDate.date' => 'Order date must be a valid date.',
            'totalAmount.required' => 'Total amount is required.',
            'totalAmount.numeric' => 'Total amount must be a number.',
            'totalAmount.min' => 'Total amount must be at least 0.',
            'discountTotal.numeric' => 'Discount total must be a number.',
            'discountTotal.min' => 'Discount total must be at least 0.',
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
