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
        return [            'currency.required' => 'El campo moneda es obligatorio.',
            'currency.string' => 'El campo moneda debe ser texto.',
            'currency.max' => 'El campo moneda no puede tener más de 255 caracteres.',
            'source.required' => 'El campo origen es obligatorio.',
            'source.string' => 'El campo origen debe ser texto.',
            'source.max' => 'El campo origen no puede tener más de 255 caracteres.',
            'scope.required' => 'El campo alcance es obligatorio.',
            'scope.string' => 'El campo alcance debe ser texto.',
            'scope.max' => 'El campo alcance no puede tener más de 255 caracteres.',
            'maxAgeDays.required' => 'El campo antigüedad máxima en días es obligatorio.',
            'maxAgeDays.integer' => 'El campo antigüedad máxima en días debe ser un número entero.',
            'tolerancePercentage.required' => 'El campo porcentaje de tolerancia es obligatorio.',
            'tolerancePercentage.numeric' => 'El campo porcentaje de tolerancia debe ser numérico.',
            'isActive.required' => 'El campo activo es obligatorio.',
            'isActive.boolean' => 'El campo activo debe ser verdadero o falso.',
        ];
    }
}
