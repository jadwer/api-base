<?php

namespace Modules\Accounting\JsonApi\V1\AccountBalances;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class AccountBalanceRequest extends ResourceRequest
{
    public function rules(): array
    {
        $accountbalance = $this->model();
        $isUpdate = $accountbalance && $accountbalance->exists;

        return [
            'companyId' => ['nullable', 'integer'],
            'accountId' => [$isUpdate ? 'sometimes' : 'required', 'integer'],
            'fiscalYear' => [$isUpdate ? 'sometimes' : 'required', 'integer'],
            'fiscalMonth' => [$isUpdate ? 'sometimes' : 'required', 'integer'],
            'openingBalance' => ['nullable', 'numeric'],
            'periodDebits' => ['nullable', 'numeric'],
            'periodCredits' => ['nullable', 'numeric'],
            'closingBalance' => ['nullable', 'numeric'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'companyId.integer' => 'El campo Company id debe ser un número entero.',
            'accountId.required' => 'El campo Account id es obligatorio.',
            'accountId.integer' => 'El campo Account id debe ser un número entero.',
            'fiscalYear.required' => 'El campo Fiscal year es obligatorio.',
            'fiscalYear.integer' => 'El campo Fiscal year debe ser un número entero.',
            'fiscalMonth.required' => 'El campo Fiscal month es obligatorio.',
            'fiscalMonth.integer' => 'El campo Fiscal month debe ser un número entero.',
            'openingBalance.numeric' => 'El campo Opening balance debe ser un número.',
            'periodDebits.numeric' => 'El campo Period debits debe ser un número.',
            'periodCredits.numeric' => 'El campo Period credits debe ser un número.',
            'closingBalance.numeric' => 'El campo Closing balance debe ser un número.',
        ];
    }
}
