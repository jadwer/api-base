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
            'orderNumber' => $this->order_number,
            'contactId' => $this->contact_id,
            'warehouseId' => $this->warehouse_id,
            'orderDate' => $this->order_date,
            'status' => $this->status,
            'totalAmount' => $this->total_amount,
            'approvalStatus' => $this->approval_status,
            'approvedAt' => $this->approved_at,
            'approvedById' => $this->approved_by_id,
            'financialStatus' => $this->financial_status,
            'apInvoiceId' => $this->ap_invoice_id,
            'invoicingStatus' => $this->invoicing_status,
            'invoicingNotes' => $this->invoicing_notes,
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
            'warehouse' => $this->relation('warehouse'),
            'purchaseOrderItems' => $this->relation('purchaseOrderItems'),
        ];
    }
}
