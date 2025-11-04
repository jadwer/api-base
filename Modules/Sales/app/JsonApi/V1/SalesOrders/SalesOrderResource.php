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
            // Foreign key - camelCase for JSON:API consistency
            'contactId' => $this->contact_id,

            // Order fields - camelCase for JSON:API consistency
            'orderNumber' => $this->order_number,
            'status' => $this->status,
            'orderDate' => $this->order_date,
            'approvedAt' => $this->approved_at,
            'deliveredAt' => $this->delivered_at,

            // Amount fields - camelCase for JSON:API consistency
            'subtotalAmount' => $this->subtotal_amount,
            'taxAmount' => $this->tax_amount,
            'discountTotal' => $this->discount_total,
            'totalAmount' => $this->total_amount,

            // Finance Integration Fields
            'arInvoiceId' => $this->ar_invoice_id,
            'invoicingStatus' => $this->invoicing_status,
            'invoicingNotes' => $this->invoicing_notes,

            // Text fields
            'notes' => $this->notes,

            // JSON fields
            'metadata' => $this->metadata,

            // Timestamps - camelCase for JSON:API consistency
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
            'contact' => $this->relation('contact'),
            'items' => $this->relation('items'),
        ];
    }
}
