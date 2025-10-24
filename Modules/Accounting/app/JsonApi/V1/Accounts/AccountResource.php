<?php

namespace Modules\Accounting\JsonApi\V1\Accounts;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class AccountResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'companyId' => $this->company_id,
            'code' => $this->code,
            'name' => $this->name,
            'accountType' => $this->account_type,
            'nature' => $this->nature,
            'level' => $this->level,
            'parentId' => $this->parent_id,
            'currency' => $this->currency,
            'isPostable' => $this->is_postable,
            'isCashFlow' => $this->is_cash_flow,
            'status' => $this->status,
            'metadata' => $this->metadata,
            "createdAt" => $this->created_at,
            "updatedAt" => $this->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
            'accounts' => $this->relation('accounts'),
            'account' => $this->relation('account'),
            'journalLine' => $this->relation('journalLine'),
            'journalLines' => $this->relation('journalLines'),
        ];
    }
}
