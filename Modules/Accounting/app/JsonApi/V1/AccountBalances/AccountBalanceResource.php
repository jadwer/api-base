<?php

namespace Modules\Accounting\JsonApi\V1\AccountBalances;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class AccountBalanceResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'companyId' => $this->company_id,
            'accountId' => $this->account_id,
            'fiscalYear' => $this->fiscal_year,
            'fiscalMonth' => $this->fiscal_month,
            'openingBalance' => $this->opening_balance,
            'periodDebits' => $this->period_debits,
            'periodCredits' => $this->period_credits,
            'closingBalance' => $this->closing_balance,
            "createdAt" => $this->created_at,
            "updatedAt" => $this->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [

        ];
    }
}
