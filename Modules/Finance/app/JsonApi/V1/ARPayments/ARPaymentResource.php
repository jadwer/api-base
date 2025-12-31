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
