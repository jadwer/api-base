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

        return [            'accountId' => [$isUpdate ? 'sometimes' : 'required', 'integer'],
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
        return [            'accountId.required' => 'El campo cuenta es obligatorio.',
            'accountId.integer' => 'El campo cuenta debe ser un número entero.',
            'fiscalYear.required' => 'El campo ejercicio fiscal es obligatorio.',
            'fiscalYear.integer' => 'El campo ejercicio fiscal debe ser un número entero.',
            'fiscalMonth.required' => 'El campo mes fiscal es obligatorio.',
            'fiscalMonth.integer' => 'El campo mes fiscal debe ser un número entero.',
            'openingBalance.numeric' => 'El campo saldo inicial debe ser un número.',
            'periodDebits.numeric' => 'El campo cargos del periodo debe ser un número.',
            'periodCredits.numeric' => 'El campo abonos del periodo debe ser un número.',
            'closingBalance.numeric' => 'El campo saldo final debe ser un número.',
        ];
    }
}
