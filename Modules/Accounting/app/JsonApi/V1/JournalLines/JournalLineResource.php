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
            'debit' => $this->debit,
            'credit' => $this->credit,
            'baseAmount' => $this->base_amount,
            'costCenterId' => $this->cost_center_id,
            'partnerId' => $this->partner_id,
            'memo' => $this->memo,
            "createdAt" => $this->created_at,
            "updatedAt" => $this->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
            'journalEntry' => $this->relation('journalEntry'),
            'account' => $this->relation('account'),
        ];
    }
}
