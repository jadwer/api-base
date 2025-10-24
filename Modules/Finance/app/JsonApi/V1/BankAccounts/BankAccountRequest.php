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
            'account_number' => ['nullable', 'string', 'max:255', Rule::unique('bank_accounts')->ignore($bankaccount?->id)],
            'account_name' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'max:255'],
            'gl_account_id' => ['nullable', 'integer'],
            'current_balance' => ['nullable', 'string'],
            'opening_balance' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'account_number.string' => 'El campo Account number debe ser texto.',
            'account_number.max' => 'El campo Account number no puede tener más de 255 caracteres.',
            'account_number.unique' => 'Este Account number ya está en uso.',
            'account_name.string' => 'El campo Account name debe ser texto.',
            'account_name.max' => 'El campo Account name no puede tener más de 255 caracteres.',
            'bank_name.string' => 'El campo Bank name debe ser texto.',
            'bank_name.max' => 'El campo Bank name no puede tener más de 255 caracteres.',
            'currency.string' => 'El campo Currency debe ser texto.',
            'currency.max' => 'El campo Currency no puede tener más de 255 caracteres.',
            'gl_account_id.integer' => 'El campo Gl account id debe ser un número entero.',
            'status.string' => 'El campo Status debe ser texto.',
            'status.max' => 'El campo Status no puede tener más de 255 caracteres.',
            'metadata.array' => 'El campo Metadata debe ser un arreglo.',
            'is_active.boolean' => 'El campo Is active debe ser verdadero o falso.',
        ];
    }
}
