<?php

namespace Modules\Accounting\JsonApi\V1\ExchangeRatePolicies;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class ExchangeRatePolicyRequest extends ResourceRequest
{
    public function rules(): array
    {
        $exchangeratepolicy = $this->model();
        $isUpdate = $exchangeratepolicy && $exchangeratepolicy->exists;

        return [            'currency' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'source' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'scope' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'max_age_days' => [$isUpdate ? 'sometimes' : 'required', 'integer'],
            'tolerance_percentage' => [$isUpdate ? 'sometimes' : 'required', 'string'],
            'require_approval_over' => ['nullable', 'string'],
            'isActive' => [$isUpdate ? 'sometimes' : 'required', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [            'currency.required' => 'El campo Currency es obligatorio.',
            'currency.string' => 'El campo Currency debe ser texto.',
            'currency.max' => 'El campo Currency no puede tener más de 255 caracteres.',
            'source.required' => 'El campo Source es obligatorio.',
            'source.string' => 'El campo Source debe ser texto.',
            'source.max' => 'El campo Source no puede tener más de 255 caracteres.',
            'scope.required' => 'El campo Scope es obligatorio.',
            'scope.string' => 'El campo Scope debe ser texto.',
            'scope.max' => 'El campo Scope no puede tener más de 255 caracteres.',
            'max_age_days.required' => 'El campo Max age days es obligatorio.',
            'max_age_days.integer' => 'El campo Max age days debe ser un número entero.',
            'tolerance_percentage.required' => 'El campo Tolerance percentage es obligatorio.',
            'isActive.required' => 'El campo Is active es obligatorio.',
            'isActive.boolean' => 'El campo Is active debe ser verdadero o falso.',
        ];
    }
}
