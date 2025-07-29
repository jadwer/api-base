<?php

namespace Modules\Sales\JsonApi\V1\SalesOrders;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class SalesOrderResource extends JsonApiResource
{
    /**
     * Get the resource attributes.
     */
    public function attributes($request): iterable
    {
        return [
            // Direct foreign key field - both snake_case and camelCase for compatibility
            'customer_id' => $this->customer_id,
            'customerId' => $this->customer_id,
            
            // Order fields - snake_case for existing compatibility
            'order_number' => $this->order_number,
            'status' => $this->status,
            'order_date' => $this->order_date,
            'approved_at' => $this->approved_at,
            'delivered_at' => $this->delivered_at,
            
            // Amount fields - snake_case for existing compatibility
            'total_amount' => $this->total_amount,
            'discount_total' => $this->discount_total,
            
            // Text fields
            'notes' => $this->notes,
            
            // JSON fields
            'metadata' => $this->metadata,
            
            // Timestamps - snake_case for existing compatibility
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Get the resource relationships.
     */
    public function relationships($request): iterable
    {
        return [
            'customer' => $this->relation('customer'),
            'items' => $this->relation('items'),
        ];
    }
}
