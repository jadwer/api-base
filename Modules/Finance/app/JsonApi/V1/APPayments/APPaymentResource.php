<?php

namespace Modules\Finance\JsonApi\V1\APPayments;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class APPaymentResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'contactId' => $this->contact_id,
            'paymentDate' => $this->payment_date,
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
            'aPInvoicePayments' => $this->relation('aPInvoicePayments'),
            'bankAccount' => $this->relation('bankAccount'),
        ];
    }
}
