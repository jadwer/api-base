<?php

namespace Modules\Finance\JsonApi\V1\BankStatementLines;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class BankStatementLineResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'bankStatementId' => $this->bank_statement_id,
            'txnDate' => $this->txn_date,
            'amount' => $this->amount,
            'counterparty' => $this->counterparty,
            'reference' => $this->reference,
            'fitid' => $this->fitid,
            'status' => $this->status,
            "createdAt" => $this->created_at,
            "updatedAt" => $this->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
            'bankStatement' => $this->relation('bankStatement'),
        ];
    }
}
