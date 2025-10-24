<?php

namespace Modules\Finance\JsonApi\V1\PaymentApplications;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class PaymentApplicationResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'paymentId' => $this->payment_id,
            'arInvoiceId' => $this->ar_invoice_id,
            'amount' => $this->amount,
            'applicationDate' => $this->application_date,
            'notes' => $this->notes,
            'isActive' => $this->is_active,
            "createdAt" => $this->created_at,
            "updatedAt" => $this->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
            'aRInvoices' => $this->relation('aRInvoices'),
            'payments' => $this->relation('payments'),
            'payment' => $this->relation('payment'),
            'aRInvoice' => $this->relation('aRInvoice'),
        ];
    }
}
