<?php

namespace Modules\Accounting\JsonApi\V1\ExchangeRates;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class ExchangeRateRequest extends ResourceRequest
{
    public function rules(): array
    {
        $exchangerate = $this->model();
        
        return [
            'from_currency' => ['required', 'string', 'max:255'],
            'to_currency' => ['required', 'string', 'max:255'],
            'rate' => ['required', 'string'],
            'effective_date' => ['required', 'date'],
            'source' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'from_currency.required' => 'El campo From currency es obligatorio.',
            'from_currency.string' => 'El campo From currency debe ser texto.',
            'from_currency.max' => 'El campo From currency no puede tener más de 255 caracteres.',
            'to_currency.required' => 'El campo To currency es obligatorio.',
            'to_currency.string' => 'El campo To currency debe ser texto.',
            'to_currency.max' => 'El campo To currency no puede tener más de 255 caracteres.',
            'rate.required' => 'El campo Rate es obligatorio.',
            'effective_date.required' => 'El campo Effective date es obligatorio.',
            'effective_date.date' => 'El campo Effective date debe ser una fecha válida.',
            'source.string' => 'El campo Source debe ser texto.',
            'source.max' => 'El campo Source no puede tener más de 255 caracteres.',
            'status.required' => 'El campo Status es obligatorio.',
            'status.string' => 'El campo Status debe ser texto.',
            'status.max' => 'El campo Status no puede tener más de 255 caracteres.',
            'metadata.array' => 'El campo Metadata debe ser un arreglo.',
        ];
    }
}
