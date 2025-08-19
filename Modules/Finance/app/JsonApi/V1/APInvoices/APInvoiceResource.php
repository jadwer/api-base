<?php

namespace Modules\Finance\JsonApi\V1\APInvoices;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class APInvoiceResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'contactId' => $this->contact_id,
            'invoiceNumber' => $this->invoice_number,
            'invoiceDate' => $this->invoice_date,
            'dueDate' => $this->due_date,
            'currency' => $this->currency,
            'exchangeRate' => $this->exchange_rate,
            'subtotal' => $this->subtotal,
            'taxTotal' => $this->tax_total,
            'total' => $this->total,
            'status' => $this->status,
            "createdAt" => $this->created_at,
            "updatedAt" => $this->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
            'aPInvoiceLines' => $this->relation('aPInvoiceLines'),
            'aPInvoicePayments' => $this->relation('aPInvoicePayments'),
        ];
    }
}
