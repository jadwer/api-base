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
            'receivedQuantity' => $this->received_quantity,
            'unitPrice' => $this->unit_price,
            'discount' => $this->discount,
            'subtotal' => $this->subtotal,
            'total' => $this->total,
            'invoicedQuantity' => $this->invoiced_quantity,
            'invoicedAmount' => $this->invoiced_amount,
            'metadata' => $this->metadata,
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
