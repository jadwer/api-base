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
            'base_currency' => ['required', 'string', 'max:255'],
            'quote_currency' => ['required', 'string', 'max:255'],
            'rate_date' => ['required', 'date'],
            'rate' => ['required', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'base_currency.required' => 'El campo Base currency es obligatorio.',
            'base_currency.string' => 'El campo Base currency debe ser texto.',
            'base_currency.max' => 'El campo Base currency no puede tener más de 255 caracteres.',
            'quote_currency.required' => 'El campo Quote currency es obligatorio.',
            'quote_currency.string' => 'El campo Quote currency debe ser texto.',
            'quote_currency.max' => 'El campo Quote currency no puede tener más de 255 caracteres.',
            'rate_date.required' => 'El campo Rate date es obligatorio.',
            'rate_date.date' => 'El campo Rate date debe ser una fecha válida.',
            'rate.required' => 'El campo Rate es obligatorio.',
        ];
    }
}
