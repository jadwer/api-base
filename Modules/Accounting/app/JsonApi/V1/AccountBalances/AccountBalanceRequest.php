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
            'company_id' => ['required', 'string'],
            'account_id' => ['required', 'string'],
            'fiscal_year' => ['required', 'integer'],
            'fiscal_month' => ['required', 'integer'],
            'opening_balance' => ['required', 'string'],
            'period_debits' => ['required', 'string'],
            'period_credits' => ['required', 'string'],
            'closing_balance' => ['required', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'El campo Company id es obligatorio.',
            'account_id.required' => 'El campo Account id es obligatorio.',
            'fiscal_year.required' => 'El campo Fiscal year es obligatorio.',
            'fiscal_year.integer' => 'El campo Fiscal year debe ser un número entero.',
            'fiscal_month.required' => 'El campo Fiscal month es obligatorio.',
            'fiscal_month.integer' => 'El campo Fiscal month debe ser un número entero.',
            'opening_balance.required' => 'El campo Opening balance es obligatorio.',
            'period_debits.required' => 'El campo Period debits es obligatorio.',
            'period_credits.required' => 'El campo Period credits es obligatorio.',
            'closing_balance.required' => 'El campo Closing balance es obligatorio.',
        ];
    }
}
