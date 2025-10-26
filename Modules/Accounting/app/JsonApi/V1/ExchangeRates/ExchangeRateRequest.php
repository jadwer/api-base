<?php

namespace Modules\Accounting\JsonApi\V1\ExchangeRates;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class ExchangeRateRequest extends ResourceRequest
{
    public function rules(): array
    {
        $exchangerate = $this->model();
        $isUpdate = $ExchangeRate && $ExchangeRate->exists;

        
        return [
            'fromCurrency' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'toCurrency' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'rate' => [$isUpdate ? 'sometimes' : 'required', 'numeric'],
            'effectiveDate' => [$isUpdate ? 'sometimes' : 'required', 'date'],
            'source' => ['nullable', 'string', 'max:255'],
            'status' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'fromCurrency.required' => 'El campo From currency es obligatorio.',
            'fromCurrency.string' => 'El campo From currency debe ser texto.',
            'fromCurrency.max' => 'El campo From currency no puede tener más de 255 caracteres.',
            'toCurrency.required' => 'El campo To currency es obligatorio.',
            'toCurrency.string' => 'El campo To currency debe ser texto.',
            'toCurrency.max' => 'El campo To currency no puede tener más de 255 caracteres.',
            'rate.required' => 'El campo Rate es obligatorio.',
            'effectiveDate.required' => 'El campo Effective date es obligatorio.',
            'effectiveDate.date' => 'El campo Effective date debe ser una fecha válida.',
            'source.string' => 'El campo Source debe ser texto.',
            'source.max' => 'El campo Source no puede tener más de 255 caracteres.',
            'status.required' => 'El campo Status es obligatorio.',
            'status.string' => 'El campo Status debe ser texto.',
            'status.max' => 'El campo Status no puede tener más de 255 caracteres.',
            'metadata.array' => 'El campo Metadata debe ser un arreglo.',
        ];
    }
}
