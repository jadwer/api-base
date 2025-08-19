<?php

namespace Modules\Accounting\JsonApi\V1\FiscalPeriods;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class FiscalPeriodRequest extends ResourceRequest
{
    public function rules(): array
    {
        $fiscalperiod = $this->model();
        
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('fiscal_periods')->ignore($fiscalperiod?->id)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'status' => ['required', 'string', 'max:255'],
            'allow_backpost' => ['required', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El campo Name es obligatorio.',
            'name.string' => 'El campo Name debe ser texto.',
            'name.max' => 'El campo Name no puede tener más de 255 caracteres.',
            'name.unique' => 'Este Name ya está en uso.',
            'start_date.required' => 'El campo Start date es obligatorio.',
            'start_date.date' => 'El campo Start date debe ser una fecha válida.',
            'end_date.required' => 'El campo End date es obligatorio.',
            'end_date.date' => 'El campo End date debe ser una fecha válida.',
            'status.required' => 'El campo Status es obligatorio.',
            'status.string' => 'El campo Status debe ser texto.',
            'status.max' => 'El campo Status no puede tener más de 255 caracteres.',
            'allow_backpost.required' => 'El campo Allow backpost es obligatorio.',
            'allow_backpost.boolean' => 'El campo Allow backpost debe ser verdadero o falso.',
        ];
    }
}
