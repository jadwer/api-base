<?php

namespace Modules\Finance\JsonApi\V1\BankAccounts;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class BankAccountRequest extends ResourceRequest
{
    public function rules(): array
    {
        $bankaccount = $this->model();
        
        // Columnas NOT NULL: requeridas al crear para responder 422 y no 500
        $required = $this->isCreating() ? 'required' : 'sometimes';

        return [
            'accountNumber' => [$required, 'string', 'max:255', Rule::unique('bank_accounts', 'account_number')->ignore($bankaccount?->id)],
            'accountName' => [$required, 'string', 'max:255'],
            'bankName' => [$required, 'string', 'max:255'],
            'currency' => ['sometimes', 'string', 'max:255'],
            'glAccountId' => [$required, 'integer', 'exists:accounts,id'],
            'currentBalance' => ['nullable', 'numeric'],
            'openingBalance' => ['nullable', 'numeric'],
            'status' => ['sometimes', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
            'isActive' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'accountNumber.required' => 'El numero de cuenta es obligatorio.',
            'accountName.required' => 'El nombre de la cuenta es obligatorio.',
            'bankName.required' => 'El banco es obligatorio.',
            'glAccountId.required' => 'La cuenta contable es obligatoria.',
            'glAccountId.exists' => 'La cuenta contable no existe.',
            'accountNumber.string' => 'El campo número de cuenta debe ser texto.',
            'accountNumber.max' => 'El campo número de cuenta no puede tener más de 255 caracteres.',
            'accountNumber.unique' => 'Este número de cuenta ya está en uso.',
            'accountName.string' => 'El campo nombre de la cuenta debe ser texto.',
            'accountName.max' => 'El campo nombre de la cuenta no puede tener más de 255 caracteres.',
            'bankName.string' => 'El campo banco debe ser texto.',
            'bankName.max' => 'El campo banco no puede tener más de 255 caracteres.',
            'currency.string' => 'El campo moneda debe ser texto.',
            'currency.max' => 'El campo moneda no puede tener más de 255 caracteres.',
            'glAccountId.integer' => 'El campo cuenta contable debe ser un número entero.',
            'currentBalance.numeric' => 'El campo saldo actual debe ser un número.',
            'openingBalance.numeric' => 'El campo saldo inicial debe ser un número.',
            'status.string' => 'El campo estado debe ser texto.',
            'status.max' => 'El campo estado no puede tener más de 255 caracteres.',
            'metadata.array' => 'El campo metadatos debe ser un arreglo.',
            'isActive.boolean' => 'El campo activo debe ser verdadero o falso.',
        ];
    }
}
