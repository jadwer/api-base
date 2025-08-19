<?php

namespace Modules\Accounting\JsonApi\V1\Accounts;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class AccountResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'accountType' => $this->account_type,
            'level' => $this->level,
            'parentId' => $this->parent_id,
            'currency' => $this->currency,
            'isPostable' => $this->is_postable,
            'status' => $this->status,
            'metadata' => $this->metadata,
            "createdAt" => $this->created_at,
            "updatedAt" => $this->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
            'journalLine' => $this->relation('journalLine'),
            'account' => $this->relation('account'),
        ];
    }
}
