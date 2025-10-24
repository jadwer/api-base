<?php

namespace Modules\Finance\JsonApi\V1\Payments;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class PaymentResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'paymentNumber' => $this->payment_number,
            'paymentDate' => $this->payment_date,
            'customerId' => $this->customer_id,
            'bankAccountId' => $this->bank_account_id,
            'paymentMethodId' => $this->payment_method_id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'appliedAmount' => $this->applied_amount,
            'unappliedAmount' => $this->unapplied_amount,
            'status' => $this->status,
            'journalEntryId' => $this->journal_entry_id,
            'reference' => $this->reference,
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
            'customer' => $this->relation('customer'),
            'bankAccount' => $this->relation('bankAccount'),
            'paymentMethod' => $this->relation('paymentMethod'),
            'journalEntry' => $this->relation('journalEntry'),
            'paymentApplications' => $this->relation('paymentApplications'),
            'paymentApplication' => $this->relation('paymentApplication'),
        ];
    }
}
