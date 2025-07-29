<?php

namespace Modules\Sales\JsonApi\V1\SalesOrderItems;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class SalesOrderItemResource extends JsonApiResource
{
    /**
     * Get the resource attributes.
     */
    public function attributes($request): iterable
    {
        return [
            // Direct foreign key fields (following SalesOrder pattern)
            'salesOrderId' => $this->sales_order_id,
            'productId' => $this->product_id,
            
            // Numeric fields (camelCase in JSON API)
            'quantity' => $this->quantity,
            'unitPrice' => $this->unit_price,
            'discount' => $this->discount,
            'total' => $this->total,
            
            // JSON fields
            'metadata' => $this->metadata,
            
            // Timestamps
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }

    /**
     * Get the resource relationships.
     */
    public function relationships($request): iterable
    {
        return [
            'salesOrder' => $this->relation('salesOrder'),
            'product' => $this->relation('product'),
        ];
    }
}
