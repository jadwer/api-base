<?php

namespace Modules\Purchase\JsonApi\V1\PurchaseOrderItems;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

class PurchaseOrderItemResource extends JsonApiResource
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
            'purchaseOrderId' => $this->purchase_order_id,
            'productId' => $this->product_id,
            'quantity' => $this->quantity,
            'unitPrice' => $this->unit_price,
            'subtotal' => $this->subtotal,
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
            $this->relation('purchaseOrder'),
            $this->relation('product'),
        ];
    }
}
