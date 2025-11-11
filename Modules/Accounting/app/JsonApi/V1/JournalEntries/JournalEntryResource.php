<?php

namespace Modules\Accounting\JsonApi\V1\JournalEntries;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class JournalEntryResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'journalId' => $this->journal_id,
            'fiscalPeriodId' => $this->fiscal_period_id,
            'number' => $this->number,
            'date' => $this->date,
            'reference' => $this->reference,
            'description' => $this->description,
            'totalDebit' => $this->total_debit,
            'totalCredit' => $this->total_credit,
            'companyId' => $this->company_id,
            'status' => $this->status,
            'approvedAt' => $this->approved_at,
            'approvedById' => $this->approved_by_id,
            'postedAt' => $this->posted_at,
            'postedById' => $this->posted_by_id,
            'reversalOfId' => $this->reversal_of_id,
            'reversalReason' => $this->reversal_reason,
            'metadata' => $this->metadata,
            "createdAt" => $this->created_at,
            "updatedAt" => $this->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
            'journal' => $this->relation('journal'),
            'fiscalPeriod' => $this->relation('fiscalPeriod'),
            'journalEntry' => $this->relation('journalEntry'),
            'journalEntry' => $this->relation('journalEntry'),
            'journals' => $this->relation('journals'),
            'fiscalPeriods' => $this->relation('fiscalPeriods'),
            'journalLine' => $this->relation('journalLine'),
            'journalLines' => $this->relation('journalLines'),
        ];
    }
}
