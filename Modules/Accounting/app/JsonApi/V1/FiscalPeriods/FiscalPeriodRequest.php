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
            'name.required' => 'El campo Name es obligatorio.',
            'name.string' => 'El campo Name debe ser texto.',
            'name.max' => 'El campo Name no puede tener más de 255 caracteres.',
            'name.unique' => 'Este Name ya está en uso.',
            'year.required' => 'El campo Year es obligatorio.',
            'year.integer' => 'El campo Year debe ser un número entero.',
            'month.required' => 'El campo Month es obligatorio.',
            'month.integer' => 'El campo Month debe ser un número entero.',
            'startDate.required' => 'El campo Start date es obligatorio.',
            'startDate.date' => 'El campo Start date debe ser una fecha válida.',
            'endDate.required' => 'El campo End date es obligatorio.',
            'endDate.date' => 'El campo End date debe ser una fecha válida.',
            'status.required' => 'El campo Status es obligatorio.',
            'status.string' => 'El campo Status debe ser texto.',
            'status.in' => 'El campo Status debe ser: open, closed o locked.',
            'metadata.array' => 'El campo Metadata debe ser un arreglo.',
        ];
    }
}
