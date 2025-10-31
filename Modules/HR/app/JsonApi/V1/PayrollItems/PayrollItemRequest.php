<?php

namespace Modules\HR\JsonApi\V1\PayrollItems;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class PayrollItemRequest extends ResourceRequest
{
    public function rules(): array
    {
        return [
            'basicSalary' => ['required', 'numeric', 'min:0'],
            'overtimePay' => ['sometimes', 'numeric', 'min:0'],
            'bonuses' => ['sometimes', 'numeric', 'min:0'],
            'deductions' => ['sometimes', 'numeric', 'min:0'],
            'netPay' => ['sometimes', 'numeric'],
            'status' => ['sometimes', 'string', 'in:draft,pending,paid'],
            'paidAt' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'employee' => ['required', JsonApiRule::toOne()],
            'payrollPeriod' => ['required', JsonApiRule::toOne()],
        ];
    }

    public function messages(): array
    {
        return [
            'basicSalary.required' => 'El salario básico es obligatorio.',
            'basicSalary.numeric' => 'El salario básico debe ser un número.',
            'basicSalary.min' => 'El salario básico no puede ser negativo.',
            'overtimePay.numeric' => 'El pago de horas extras debe ser un número.',
            'overtimePay.min' => 'El pago de horas extras no puede ser negativo.',
            'bonuses.numeric' => 'Las bonificaciones deben ser un número.',
            'bonuses.min' => 'Las bonificaciones no pueden ser negativas.',
            'deductions.numeric' => 'Las deducciones deben ser un número.',
            'deductions.min' => 'Las deducciones no pueden ser negativas.',
            'netPay.numeric' => 'El pago neto debe ser un número.',
            'status.in' => 'El estado debe ser uno de: draft, pending, paid.',
            'paidAt.date' => 'La fecha de pago debe ser una fecha válida.',
            'notes.string' => 'Las notas deben ser texto.',
            'employee.required' => 'El empleado es obligatorio.',
            'payrollPeriod.required' => 'El período de nómina es obligatorio.',
        ];
    }
}
