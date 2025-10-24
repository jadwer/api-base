<?php

namespace Modules\Accounting\JsonApi\V1\Journals;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class JournalResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'prefix' => $this->prefix,
            'type' => $this->type,
            'status' => $this->status,
            'metadata' => $this->metadata,
            "createdAt" => $this->created_at,
            "updatedAt" => $this->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
            'journalSequence' => $this->relation('journalSequence'),
            'journalSequences' => $this->relation('journalSequences'),
            'journalEntry' => $this->relation('journalEntry'),
            'journalEntries' => $this->relation('journalEntries'),
        ];
    }
}
