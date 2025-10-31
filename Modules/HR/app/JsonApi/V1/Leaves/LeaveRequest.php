<?php

namespace Modules\HR\JsonApi\V1\Leaves;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class LeaveRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'startDate' => ['required', 'date'],
            'endDate' => ['required', 'date', 'after_or_equal:startDate'],
            'daysRequested' => ['required', 'integer', 'min:1'],
            'status' => ['sometimes', 'string', 'in:pending,approved,rejected,cancelled'],
            'reason' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'approvedAt' => ['nullable', 'date'],
            'employee' => ['required', JsonApiRule::toOne()],
            'leaveType' => ['required', JsonApiRule::toOne()],
            'approver' => ['nullable', JsonApiRule::toOne()],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'startDate.required' => 'La fecha de inicio es obligatoria.',
            'startDate.date' => 'La fecha de inicio debe ser una fecha válida.',
            'endDate.required' => 'La fecha de fin es obligatoria.',
            'endDate.date' => 'La fecha de fin debe ser una fecha válida.',
            'endDate.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'daysRequested.required' => 'Los días solicitados son obligatorios.',
            'daysRequested.integer' => 'Los días solicitados deben ser un número entero.',
            'daysRequested.min' => 'Debe solicitar al menos 1 día.',
            'status.in' => 'El estado debe ser uno de: pending, approved, rejected, cancelled.',
            'reason.string' => 'El motivo debe ser texto.',
            'notes.string' => 'Las notas deben ser texto.',
            'approvedAt.date' => 'La fecha de aprobación debe ser una fecha válida.',
            'employee.required' => 'El empleado es obligatorio.',
            'leaveType.required' => 'El tipo de permiso es obligatorio.',
        ];
    }
}
