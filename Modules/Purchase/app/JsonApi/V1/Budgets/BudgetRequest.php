<?php

namespace Modules\Purchase\JsonApi\V1\Budgets;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;
use Modules\Purchase\Models\Budget;

class BudgetRequest extends ResourceRequest
{
    public function rules(): array
    {
        $budget = $this->model();
        $isUpdate = $this->isMethod('PATCH');

        return [
            'name' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:255',
            ],
            'code' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:50',
                Rule::unique('budgets', 'code')->ignore($budget?->id),
            ],
            'description' => ['nullable', 'string'],
            'budgetType' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                Rule::in([
                    Budget::TYPE_DEPARTMENT,
                    Budget::TYPE_CATEGORY,
                    Budget::TYPE_PROJECT,
                    Budget::TYPE_SUPPLIER,
                    Budget::TYPE_GENERAL,
                ]),
            ],
            'departmentCode' => ['nullable', 'string', 'max:50'],
            'categoryId' => ['nullable', 'integer', 'exists:categories,id'],
            'projectCode' => ['nullable', 'string', 'max:50'],
            'contactId' => ['nullable', 'integer', 'exists:contacts,id'],
            'periodType' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                Rule::in([
                    Budget::PERIOD_MONTHLY,
                    Budget::PERIOD_QUARTERLY,
                    Budget::PERIOD_ANNUAL,
                    Budget::PERIOD_CUSTOM,
                ]),
            ],
            'startDate' => [$isUpdate ? 'sometimes' : 'required', 'date'],
            'endDate' => [$isUpdate ? 'sometimes' : 'required', 'date', 'after_or_equal:startDate'],
            'fiscalYear' => ['nullable', 'integer', 'min:2020', 'max:2100'],
            'budgetedAmount' => [$isUpdate ? 'sometimes' : 'required', 'numeric', 'min:0'],
            'warningThreshold' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'criticalThreshold' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'hardLimit' => ['nullable', 'boolean'],
            'allowOvercommit' => ['nullable', 'boolean'],
            'isActive' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del presupuesto es obligatorio.',
            'code.required' => 'El código del presupuesto es obligatorio.',
            'code.unique' => 'Este código de presupuesto ya existe.',
            'budgetType.required' => 'El tipo de presupuesto es obligatorio.',
            'budgetType.in' => 'El tipo de presupuesto no es válido.',
            'periodType.required' => 'El tipo de período es obligatorio.',
            'periodType.in' => 'El tipo de período no es válido.',
            'startDate.required' => 'La fecha de inicio es obligatoria.',
            'endDate.required' => 'La fecha de fin es obligatoria.',
            'endDate.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'budgetedAmount.required' => 'El monto presupuestado es obligatorio.',
            'budgetedAmount.min' => 'El monto presupuestado no puede ser negativo.',
            'categoryId.exists' => 'La categoría seleccionada no existe.',
            'contactId.exists' => 'El proveedor seleccionado no existe.',
        ];
    }
}
