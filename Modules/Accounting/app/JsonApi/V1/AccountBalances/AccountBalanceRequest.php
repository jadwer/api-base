<?php

namespace Modules\Accounting\JsonApi\V1\AccountBalances;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class AccountBalanceRequest extends ResourceRequest
{
    public function rules(): array
    {
        $accountbalance = $this->model();
        
        return [
            'companyId' => ['required', 'string'],
            'accountId' => ['required', 'string'],
            'fiscalYear' => ['required', 'integer'],
            'fiscalMonth' => ['required', 'integer'],
            'openingBalance' => ['required', 'string'],
            'period_debits' => ['required', 'string'],
            'period_credits' => ['required', 'string'],
            'closingBalance' => ['required', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'companyId.required' => 'El campo Company id es obligatorio.',
            'accountId.required' => 'El campo Account id es obligatorio.',
            'fiscalYear.required' => 'El campo Fiscal year es obligatorio.',
            'fiscalYear.integer' => 'El campo Fiscal year debe ser un número entero.',
            'fiscalMonth.required' => 'El campo Fiscal month es obligatorio.',
            'fiscalMonth.integer' => 'El campo Fiscal month debe ser un número entero.',
            'openingBalance.required' => 'El campo Opening balance es obligatorio.',
            'period_debits.required' => 'El campo Period debits es obligatorio.',
            'period_credits.required' => 'El campo Period credits es obligatorio.',
            'closingBalance.required' => 'El campo Closing balance es obligatorio.',
        ];
    }
}
