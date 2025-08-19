<?php

namespace Modules\Finance\JsonApi\V1\APInvoicePayments;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class APInvoicePaymentResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'apInvoiceId' => $this->ap_invoice_id,
            'apPaymentId' => $this->ap_payment_id,
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
            'aPPayment' => $this->relation('aPPayment'),
            'aPInvoice' => $this->relation('aPInvoice'),
        ];
    }
}
