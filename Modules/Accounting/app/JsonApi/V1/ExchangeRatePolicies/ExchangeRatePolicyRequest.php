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
            'maxAgeDays' => [$isUpdate ? 'sometimes' : 'required', 'integer'],
            'tolerancePercentage' => [$isUpdate ? 'sometimes' : 'required', 'numeric'],
            'requireApprovalOver' => ['nullable', 'numeric'],
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
            'maxAgeDays.required' => 'El campo Max age days es obligatorio.',
            'maxAgeDays.integer' => 'El campo Max age days debe ser un número entero.',
            'tolerancePercentage.required' => 'El campo Tolerance percentage es obligatorio.',
            'tolerancePercentage.numeric' => 'El campo Tolerance percentage debe ser numérico.',
            'isActive.required' => 'El campo Is active es obligatorio.',
            'isActive.boolean' => 'El campo Is active debe ser verdadero o falso.',
        ];
    }
}
