<?php

namespace Modules\Accounting\JsonApi\V1\FiscalPeriods;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class FiscalPeriodResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'name' => $this->name,
            'year' => $this->year,
            'month' => $this->month,
            'startDate' => $this->start_date,
            'endDate' => $this->end_date,
            'status' => $this->status,
            'closedAt' => $this->closed_at,
            'closedById' => $this->closed_by_id,
            'closingEntryId' => $this->closing_entry_id,
            'metadata' => $this->metadata,
            "createdAt" => $this->created_at,
            "updatedAt" => $this->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
            'journalEntry' => $this->relation('journalEntry'),
            'journalEntries' => $this->relation('journalEntries'),
        ];
    }
}
