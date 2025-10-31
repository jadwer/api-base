<?php

namespace Modules\HR\JsonApi\V1\Attendances;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class AttendanceRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     */
    public function rules(): array
    {
        return [
            'date' => [
                'required',
                'date',
            ],
            'checkIn' => [
                'nullable',
                'date_format:H:i',
            ],
            'checkOut' => [
                'nullable',
                'date_format:H:i',
                'after:checkIn',
            ],
            'status' => [
                'required',
                'string',
                'in:present,absent,late,half_day,on_leave',
            ],
            'notes' => [
                'nullable',
                'string',
            ],
            'employee' => [
                'required',
                JsonApiRule::toOne(),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors in Spanish.
     */
    public function messages(): array
    {
        return [
            'date.required' => 'La fecha es obligatoria.',
            'date.date' => 'La fecha debe ser una fecha válida.',
            'checkIn.date_format' => 'La hora de entrada debe tener el formato HH:MM.',
            'checkOut.date_format' => 'La hora de salida debe tener el formato HH:MM.',
            'checkOut.after' => 'La hora de salida debe ser posterior a la hora de entrada.',
            'status.required' => 'El estado es obligatorio.',
            'status.string' => 'El estado debe ser un texto.',
            'status.in' => 'El estado debe ser uno de: present, absent, late, half_day, on_leave.',
            'notes.string' => 'Las notas deben ser un texto.',
            'employee.required' => 'El empleado es obligatorio.',
            'employee.to_one' => 'El empleado debe ser una relación válida.',
        ];
    }
}
