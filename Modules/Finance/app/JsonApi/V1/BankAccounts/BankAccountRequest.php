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
            'bankName' => ['required', 'string', 'max:255'],
            'accountNumber' => ['required', 'string', 'max:255', Rule::unique('bank_accounts', 'account_number')->ignore($bankaccount?->id)],
            'clabe' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', 'string', 'max:255'],
            'accountType' => ['required', 'string', 'max:255'],
            'openingBalance' => ['required', 'numeric'],
            'status' => ['required', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'bankName.required' => 'El campo Bank name es obligatorio.',
            'bankName.string' => 'El campo Bank name debe ser texto.',
            'bankName.max' => 'El campo Bank name no puede tener más de 255 caracteres.',
            'accountNumber.required' => 'El campo Account number es obligatorio.',
            'accountNumber.string' => 'El campo Account number debe ser texto.',
            'accountNumber.max' => 'El campo Account number no puede tener más de 255 caracteres.',
            'accountNumber.unique' => 'Este Account number ya está en uso.',
            'clabe.string' => 'El campo Clabe debe ser texto.',
            'clabe.max' => 'El campo Clabe no puede tener más de 255 caracteres.',
            'currency.required' => 'El campo Currency es obligatorio.',
            'currency.string' => 'El campo Currency debe ser texto.',
            'currency.max' => 'El campo Currency no puede tener más de 255 caracteres.',
            'accountType.required' => 'El campo Account type es obligatorio.',
            'accountType.string' => 'El campo Account type debe ser texto.',
            'accountType.max' => 'El campo Account type no puede tener más de 255 caracteres.',
            'openingBalance.required' => 'El campo Opening balance es obligatorio.',
            'openingBalance.numeric' => 'El campo Opening balance debe ser un número.',
            'status.required' => 'El campo Status es obligatorio.',
            'status.string' => 'El campo Status debe ser texto.',
            'status.max' => 'El campo Status no puede tener más de 255 caracteres.',
        ];
    }
}
