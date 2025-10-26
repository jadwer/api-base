<?php

namespace Modules\Finance\JsonApi\V1\BankAccounts;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class BankAccountRequest extends ResourceRequest
{
    public function rules(): array
    {
        $bankaccount = $this->model();
        
        return [
            'accountNumber' => ['nullable', 'string', 'max:255', Rule::unique('bank_accounts', 'account_number')->ignore($bankaccount?->id)],
            'accountName' => ['nullable', 'string', 'max:255'],
            'bankName' => ['nullable', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'max:255'],
            'glAccountId' => ['nullable', 'integer'],
            'currentBalance' => ['nullable', 'numeric'],
            'openingBalance' => ['nullable', 'numeric'],
            'status' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
            'isActive' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'accountNumber.string' => 'El campo Account number debe ser texto.',
            'accountNumber.max' => 'El campo Account number no puede tener más de 255 caracteres.',
            'accountNumber.unique' => 'Este Account number ya está en uso.',
            'accountName.string' => 'El campo Account name debe ser texto.',
            'accountName.max' => 'El campo Account name no puede tener más de 255 caracteres.',
            'bankName.string' => 'El campo Bank name debe ser texto.',
            'bankName.max' => 'El campo Bank name no puede tener más de 255 caracteres.',
            'currency.string' => 'El campo Currency debe ser texto.',
            'currency.max' => 'El campo Currency no puede tener más de 255 caracteres.',
            'glAccountId.integer' => 'El campo Gl account id debe ser un número entero.',
            'currentBalance.numeric' => 'El campo Current balance debe ser un número.',
            'openingBalance.numeric' => 'El campo Opening balance debe ser un número.',
            'status.string' => 'El campo Status debe ser texto.',
            'status.max' => 'El campo Status no puede tener más de 255 caracteres.',
            'metadata.array' => 'El campo Metadata debe ser un arreglo.',
            'isActive.boolean' => 'El campo Is active debe ser verdadero o falso.',
        ];
    }
}
