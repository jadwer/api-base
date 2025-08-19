<?php

namespace Modules\Finance\JsonApi\V1\BankAccounts;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class BankAccountResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'bankName' => $this->bank_name,
            'accountNumber' => $this->account_number,
            'clabe' => $this->clabe,
            'currency' => $this->currency,
            'accountType' => $this->account_type,
            'openingBalance' => $this->opening_balance,
            'status' => $this->status,
            "createdAt" => $this->created_at,
            "updatedAt" => $this->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
            'bankStatement' => $this->relation('bankStatement'),
            'aPPayment' => $this->relation('aPPayment'),
            'aRReceipt' => $this->relation('aRReceipt'),
        ];
    }
}
