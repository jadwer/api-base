<?php

namespace Modules\Purchase\JsonApi\V1\PurchaseOrders;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

class PurchaseOrderResource extends JsonApiResource
{
    /**
     * Get the resource's attributes.
     *
     * @param Request|null $request
     * @return iterable
     */
    public function attributes($request): iterable
    {
        return [
            // Direct foreign key field - both snake_case and camelCase for compatibility
            'contact_id' => $this->contact_id,
            'contactId' => $this->contact_id,
            
            // Order fields
            'orderDate' => $this->order_date,
            'status' => $this->status,
            'totalAmount' => $this->total_amount,
            'notes' => $this->notes,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }

    /**
     * Get the resource's relationships.
     *
     * @param Request|null $request
     * @return iterable
     */
    public function relationships($request): iterable
    {
        return [
            'contact' => $this->relation('contact'),
            'purchaseOrderItems' => $this->relation('purchaseOrderItems'),
        ];
    }
}
