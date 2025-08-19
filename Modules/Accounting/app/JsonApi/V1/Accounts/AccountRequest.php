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
            'code' => ['required', 'string', 'max:255', Rule::unique('accounts')->ignore($account?->id)],
            'name' => ['required', 'string', 'max:255'],
            'accountType' => ['required', 'string', 'max:255'],
            'level' => ['required', 'integer'],
            'parentId' => ['nullable', 'string'],
            'currency' => ['nullable', 'string', 'max:255'],
            'isPostable' => ['required', 'boolean'],
            'status' => ['required', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'El campo Code es obligatorio.',
            'code.string' => 'El campo Code debe ser texto.',
            'code.max' => 'El campo Code no puede tener más de 255 caracteres.',
            'code.unique' => 'Este Code ya está en uso.',
            'name.required' => 'El campo Name es obligatorio.',
            'name.string' => 'El campo Name debe ser texto.',
            'name.max' => 'El campo Name no puede tener más de 255 caracteres.',
            'accountType.required' => 'El campo Account type es obligatorio.',
            'accountType.string' => 'El campo Account type debe ser texto.',
            'accountType.max' => 'El campo Account type no puede tener más de 255 caracteres.',
            'level.required' => 'El campo Level es obligatorio.',
            'level.integer' => 'El campo Level debe ser un número entero.',
            'currency.string' => 'El campo Currency debe ser texto.',
            'currency.max' => 'El campo Currency no puede tener más de 255 caracteres.',
            'isPostable.required' => 'El campo Is postable es obligatorio.',
            'isPostable.boolean' => 'El campo Is postable debe ser verdadero o falso.',
            'status.required' => 'El campo Status es obligatorio.',
            'status.string' => 'El campo Status debe ser texto.',
            'status.max' => 'El campo Status no puede tener más de 255 caracteres.',
            'metadata.array' => 'El campo Metadata debe ser un arreglo.',
        ];
    }
}
