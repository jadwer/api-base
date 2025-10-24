<?php

namespace Modules\Accounting\JsonApi\V1\JournalLines;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class JournalLineResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'journalEntryId' => $this->journal_entry_id,
            'accountId' => $this->account_id,
            'contactId' => $this->contact_id,
            'debit' => $this->debit,
            'credit' => $this->credit,
            'description' => $this->description,
            'reference' => $this->reference,
            'metadata' => $this->metadata,
            "createdAt" => $this->created_at,
            "updatedAt" => $this->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
            'journalEntry' => $this->relation('journalEntry'),
            'account' => $this->relation('account'),
            'journalEntries' => $this->relation('journalEntries'),
            'accounts' => $this->relation('accounts'),
        ];
    }
}
