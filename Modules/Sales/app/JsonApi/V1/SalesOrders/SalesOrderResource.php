<?php

namespace Modules\Sales\JsonApi\V1\SalesOrders;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class SalesOrderResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'order_number' => $this->order_number,
            'status' => $this->status,
            'order_date' => $this->order_date,
            'approved_at' => $this->approved_at,
            'delivered_at' => $this->delivered_at,
            'total_amount' => $this->total_amount,
            'discount_total' => $this->discount_total,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
    public function relationships($request): iterable
    {
        return [
            'customer' => $this->customer,
            'items' => $this->items,
        ];
    }
}
