<?php

namespace Modules\Accounting\JsonApi\V1\FiscalPeriods;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class FiscalPeriodRequest extends ResourceRequest
{
    public function rules(): array
    {
        $fiscalperiod = $this->model();
        $isUpdate = $fiscalperiod && $fiscalperiod->exists;

        
        return [
            'name' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255', Rule::unique('fiscal_periods')->ignore($fiscalperiod?->id)],
            'year' => [$isUpdate ? 'sometimes' : 'required', 'integer'],
            'month' => [$isUpdate ? 'sometimes' : 'required', 'integer'],
            'startDate' => [$isUpdate ? 'sometimes' : 'required', 'date'],
            'endDate' => [$isUpdate ? 'sometimes' : 'required', 'date'],
            'status' => [$isUpdate ? 'sometimes' : 'required', 'string', Rule::in(['open', 'closed', 'locked'])],
            'closedAt' => ['nullable', 'date'],
            'closedById' => ['nullable', 'integer'],
            'closingEntryId' => ['nullable', 'integer'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El campo nombre es obligatorio.',
            'name.string' => 'El campo nombre debe ser texto.',
            'name.max' => 'El campo nombre no puede tener más de 255 caracteres.',
            'name.unique' => 'Este nombre ya está en uso.',
            'year.required' => 'El campo año es obligatorio.',
            'year.integer' => 'El campo año debe ser un número entero.',
            'month.required' => 'El campo mes es obligatorio.',
            'month.integer' => 'El campo mes debe ser un número entero.',
            'startDate.required' => 'El campo fecha de inicio es obligatorio.',
            'startDate.date' => 'El campo fecha de inicio debe ser una fecha válida.',
            'endDate.required' => 'El campo fecha de fin es obligatorio.',
            'endDate.date' => 'El campo fecha de fin debe ser una fecha válida.',
            'status.required' => 'El campo estado es obligatorio.',
            'status.string' => 'El campo estado debe ser texto.',
            'status.in' => 'El campo estado debe ser: open, closed o locked.',
            'metadata.array' => 'El campo metadatos debe ser un arreglo.',
        ];
    }
}
