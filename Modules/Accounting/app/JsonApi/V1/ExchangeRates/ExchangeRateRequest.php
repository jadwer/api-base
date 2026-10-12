<?php

namespace Modules\Accounting\JsonApi\V1\ExchangeRates;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class ExchangeRateRequest extends ResourceRequest
{
    public function rules(): array
    {
        $exchangerate = $this->model();
        $isUpdate = $exchangerate && $exchangerate->exists;

        
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
            'fromCurrency.required' => 'El campo moneda origen es obligatorio.',
            'fromCurrency.string' => 'El campo moneda origen debe ser texto.',
            'fromCurrency.max' => 'El campo moneda origen no puede tener más de 255 caracteres.',
            'toCurrency.required' => 'El campo moneda destino es obligatorio.',
            'toCurrency.string' => 'El campo moneda destino debe ser texto.',
            'toCurrency.max' => 'El campo moneda destino no puede tener más de 255 caracteres.',
            'rate.required' => 'El campo tasa es obligatorio.',
            'effectiveDate.required' => 'El campo fecha efectiva es obligatorio.',
            'effectiveDate.date' => 'El campo fecha efectiva debe ser una fecha válida.',
            'source.string' => 'El campo origen debe ser texto.',
            'source.max' => 'El campo origen no puede tener más de 255 caracteres.',
            'status.required' => 'El campo estado es obligatorio.',
            'status.string' => 'El campo estado debe ser texto.',
            'status.max' => 'El campo estado no puede tener más de 255 caracteres.',
            'metadata.array' => 'El campo metadatos debe ser un arreglo.',
        ];
    }
}
