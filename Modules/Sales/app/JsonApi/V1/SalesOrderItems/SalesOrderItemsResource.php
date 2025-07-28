<?php

namespace Modules\Sales\JsonApi\V1\SalesOrderItems;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class SalesOrderItemsResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'sales_order_id' => $this->sales_order_id,
            'product_id' => $this->product_id,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'discount' => $this->discount,
            'total' => $this->total,
            'metadata' => $this->metadata,
        ];
    }
    public function relationships($request): iterable
    {
        return [
            'salesOrder' => $this->salesOrder,
            'product' => $this->product,
        ];
    }
}
