<?php

namespace Modules\Accounting\JsonApi\V1\Accounts;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class AccountRequest extends ResourceRequest
{
    public function rules(): array
    {
        $account = $this->model();
        
        return [
            'company_id' => ['required', 'string'],
            'code' => ['required', 'string', 'max:255', Rule::unique('accounts')->ignore($account?->id)],
            'name' => ['required', 'string', 'max:255'],
            'account_type' => ['required', 'string', 'max:255'],
            'nature' => ['required', 'string', 'max:255'],
            'level' => ['required', 'integer'],
            'parent_id' => ['nullable', 'string'],
            'currency' => ['required', 'string', 'max:255'],
            'is_postable' => ['required', 'boolean'],
            'is_cash_flow' => ['required', 'boolean'],
            'status' => ['required', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'El campo Company id es obligatorio.',
            'code.required' => 'El campo Code es obligatorio.',
            'code.string' => 'El campo Code debe ser texto.',
            'code.max' => 'El campo Code no puede tener más de 255 caracteres.',
            'code.unique' => 'Este Code ya está en uso.',
            'name.required' => 'El campo Name es obligatorio.',
            'name.string' => 'El campo Name debe ser texto.',
            'name.max' => 'El campo Name no puede tener más de 255 caracteres.',
            'account_type.required' => 'El campo Account type es obligatorio.',
            'account_type.string' => 'El campo Account type debe ser texto.',
            'account_type.max' => 'El campo Account type no puede tener más de 255 caracteres.',
            'nature.required' => 'El campo Nature es obligatorio.',
            'nature.string' => 'El campo Nature debe ser texto.',
            'nature.max' => 'El campo Nature no puede tener más de 255 caracteres.',
            'level.required' => 'El campo Level es obligatorio.',
            'level.integer' => 'El campo Level debe ser un número entero.',
            'currency.required' => 'El campo Currency es obligatorio.',
            'currency.string' => 'El campo Currency debe ser texto.',
            'currency.max' => 'El campo Currency no puede tener más de 255 caracteres.',
            'is_postable.required' => 'El campo Is postable es obligatorio.',
            'is_postable.boolean' => 'El campo Is postable debe ser verdadero o falso.',
            'is_cash_flow.required' => 'El campo Is cash flow es obligatorio.',
            'is_cash_flow.boolean' => 'El campo Is cash flow debe ser verdadero o falso.',
            'status.required' => 'El campo Status es obligatorio.',
            'status.string' => 'El campo Status debe ser texto.',
            'status.max' => 'El campo Status no puede tener más de 255 caracteres.',
            'metadata.array' => 'El campo Metadata debe ser un arreglo.',
        ];
    }
}
