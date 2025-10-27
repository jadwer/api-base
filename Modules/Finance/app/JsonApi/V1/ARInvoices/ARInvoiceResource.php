<?php

namespace Modules\Finance\JsonApi\V1\ARInvoices;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class ARInvoiceResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'invoiceNumber' => $this->invoice_number,
            'invoiceDate' => $this->invoice_date,
            'dueDate' => $this->due_date,
            'contactId' => $this->contact_id,
            'salesOrderId' => $this->sales_order_id,
            'currency' => $this->currency,
            'subtotal' => $this->subtotal,
            'taxAmount' => $this->tax_amount,
            'totalAmount' => $this->total_amount,
            'paidAmount' => $this->paid_amount,
            'status' => $this->status,
            'journalEntryId' => $this->journal_entry_id,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            'isActive' => $this->is_active,
            "createdAt" => $this->created_at,
            "updatedAt" => $this->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
            'contact' => $this->relation('contact'),
            'salesOrder' => $this->relation('salesOrder'),
            'journalEntry' => $this->relation('journalEntry'),
            'paymentApplications' => $this->relation('paymentApplications'),
        ];
    }
}
