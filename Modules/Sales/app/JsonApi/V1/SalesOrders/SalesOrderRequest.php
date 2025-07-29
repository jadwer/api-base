<?php

namespace Modules\Sales\JsonApi\V1\SalesOrders;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class SalesOrderRequest extends ResourceRequest
{
    public function rules(): array
    {
        $order = $this->model();
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'order_number' => ['required', 'string', 'max:50', Rule::unique('sales_orders', 'order_number')->ignore($order?->id)],
            'status' => ['required', Rule::in(['draft', 'pending', 'approved', 'delivered', 'cancelled'])],
            'order_date' => ['required', 'date'],
            'approved_at' => ['nullable', 'date'],
            'delivered_at' => ['nullable', 'date'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'discount_total' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
