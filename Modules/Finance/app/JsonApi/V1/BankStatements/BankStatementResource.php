<?php

namespace Modules\Finance\JsonApi\V1\BankStatements;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class BankStatementResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'bankAccountId' => $this->bank_account_id,
            'statementDate' => $this->statement_date,
            'importSource' => $this->import_source,
            "createdAt" => $this->created_at,
            "updatedAt" => $this->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
            'bankStatementLines' => $this->relation('bankStatementLines'),
            'bankAccount' => $this->relation('bankAccount'),
        ];
    }
}
