<?php

namespace Modules\Finance\JsonApi\V1\ARInvoiceReceipts;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class ARInvoiceReceiptResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'arInvoiceId' => $this->ar_invoice_id,
            'arReceiptId' => $this->ar_receipt_id,
            'amountApplied' => $this->amount_applied,
            'appliedAt' => $this->applied_at,
            'exchangeRateAtApply' => $this->exchange_rate_at_apply,
            "createdAt" => $this->created_at,
            "updatedAt" => $this->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
            'aRReceipt' => $this->relation('aRReceipt'),
            'aRInvoice' => $this->relation('aRInvoice'),
        ];
    }
}
