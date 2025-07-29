<?php

namespace Modules\Sales\JsonApi\V1\SalesOrderItems;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class SalesOrderItemRequest extends ResourceRequest
{
    public function rules(): array
    {
        $item = $this->model();
        return [
            'sales_order_id' => ['required', 'exists:sales_orders,id'],
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
