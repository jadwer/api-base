<?php

namespace Modules\Accounting\JsonApi\V1\JournalEntrys;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class JournalEntryResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'journalId' => $this->journal_id,
            'periodId' => $this->period_id,
            'number' => $this->number,
            'date' => $this->date,
            'currency' => $this->currency,
            'exchangeRate' => $this->exchange_rate,
            'reference' => $this->reference,
            'description' => $this->description,
            'status' => $this->status,
            'approvedById' => $this->approved_by_id,
            'postedById' => $this->posted_by_id,
            'postedAt' => $this->posted_at,
            'reversalOfId' => $this->reversal_of_id,
            'sourceType' => $this->source_type,
            'sourceId' => $this->source_id,
            "createdAt" => $this->created_at,
            "updatedAt" => $this->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
            'journalLines' => $this->relation('journalLines'),
            'journal' => $this->relation('journal'),
            'fiscalPeriod' => $this->relation('fiscalPeriod'),
        ];
    }
}
