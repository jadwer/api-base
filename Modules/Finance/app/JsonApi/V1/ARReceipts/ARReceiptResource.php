<?php

namespace Modules\Finance\JsonApi\V1\ARReceipts;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class ARReceiptResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'contactId' => $this->contact_id,
            'arInvoiceId' => $this->ar_invoice_id,
            'receiptDate' => $this->receipt_date,
            'paymentMethod' => $this->payment_method,
            'currency' => $this->currency,
            'amount' => $this->amount,
            'bankAccountId' => $this->bank_account_id,
            'status' => $this->status,
            "createdAt" => $this->created_at,
            "updatedAt" => $this->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
            'contact' => $this->relation('contact'),
            'arInvoice' => $this->relation('arInvoice'),
            'aRInvoiceReceipts' => $this->relation('aRInvoiceReceipts'),
            'bankAccount' => $this->relation('bankAccount'),
        ];
    }
}
