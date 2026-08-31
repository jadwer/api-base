<?php

namespace Modules\Finance\JsonApi\V1\ARPayments;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

class ARPaymentResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'paymentNumber' => $this->resource->payment_number,
            'paymentDate' => $this->resource->payment_date,
            'paymentMethod' => $this->resource->payment_method,
            'currency' => $this->resource->currency,
            'paymentAmount' => $this->resource->payment_amount,
            'appliedAmount' => $this->resource->applied_amount,
            'unappliedAmount' => $this->resource->unapplied_amount,
            'status' => $this->resource->status,
            'reference' => $this->resource->reference,
            'notes' => $this->resource->notes,
            'voidedAt' => $this->resource->voided_at,
            'voidReason' => $this->resource->void_reason,
            // Barrido Paquete B 2026-08-31: el Resource manual pisa al
            // Schema; todo campo del Schema debe estar aqui o el API
            // guarda pero nunca lo devuelve.
            'contactId' => $this->contact_id,
            'fiscalPeriodId' => $this->fiscal_period_id,
            'bankAccountId' => $this->bank_account_id,
            'journalEntryId' => $this->journal_entry_id,
            'voidedById' => $this->voided_by_id,
            'createdAt' => $this->resource->created_at,
            'updatedAt' => $this->resource->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
            $this->relation('contact'),
            $this->relation('fiscalPeriod'),
            $this->relation('bankAccount'),
            $this->relation('journalEntry'),
            $this->relation('applications'),
        ];
    }
}
