<?php

namespace Modules\Accounting\JsonApi\V1\JournalSequences;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class JournalSequenceResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'journalId' => $this->journal_id,
            'fiscalYear' => $this->fiscal_year,
            'currentNumber' => $this->current_number,
            'metadata' => $this->metadata,
            "createdAt" => $this->created_at,
            "updatedAt" => $this->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
            'journal' => $this->relation('journal'),
            'journals' => $this->relation('journals'),
        ];
    }
}
