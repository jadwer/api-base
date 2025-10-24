<?php

namespace Modules\Finance\JsonApi\V1\BankAccounts;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class BankAccountResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'accountNumber' => $this->account_number,
            'accountName' => $this->account_name,
            'bankName' => $this->bank_name,
            'currency' => $this->currency,
            'glAccountId' => $this->gl_account_id,
            'currentBalance' => $this->current_balance,
            'openingBalance' => $this->opening_balance,
            'status' => $this->status,
            'metadata' => $this->metadata,
            'isActive' => $this->is_active,
            "createdAt" => $this->created_at,
            "updatedAt" => $this->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
            'payment' => $this->relation('payment'),
            'account' => $this->relation('account'),
        ];
    }
}
