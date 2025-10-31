<?php

namespace Modules\HR\JsonApi\V1\PerformanceReviews;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class PerformanceReviewRequest extends ResourceRequest
{
    public function rules(): array
    {
        return [
            'reviewDate' => ['required', 'date'],
            'reviewPeriodStart' => ['required', 'date'],
            'reviewPeriodEnd' => ['required', 'date', 'after_or_equal:reviewPeriodStart'],
            'overallRating' => ['required', 'integer', 'between:1,5'],
            'goalsRating' => ['required', 'integer', 'between:1,5'],
            'skillsRating' => ['required', 'integer', 'between:1,5'],
            'attendanceRating' => ['required', 'integer', 'between:1,5'],
            'comments' => ['nullable', 'string'],
            'employeeComments' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', 'in:draft,submitted,reviewed,acknowledged'],
            'employee' => ['required', JsonApiRule::toOne()],
            'reviewer' => ['required', JsonApiRule::toOne()],
        ];
    }

    public function messages(): array
    {
        return [
            'reviewDate.required' => 'La fecha de revisión es obligatoria.',
            'reviewDate.date' => 'La fecha de revisión debe ser una fecha válida.',
            'reviewPeriodStart.required' => 'La fecha de inicio del período es obligatoria.',
            'reviewPeriodStart.date' => 'La fecha de inicio del período debe ser una fecha válida.',
            'reviewPeriodEnd.required' => 'La fecha de fin del período es obligatoria.',
            'reviewPeriodEnd.date' => 'La fecha de fin del período debe ser una fecha válida.',
            'reviewPeriodEnd.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'overallRating.required' => 'La calificación general es obligatoria.',
            'overallRating.integer' => 'La calificación general debe ser un número entero.',
            'overallRating.between' => 'La calificación general debe estar entre 1 y 5.',
            'goalsRating.required' => 'La calificación de objetivos es obligatoria.',
            'goalsRating.between' => 'La calificación de objetivos debe estar entre 1 y 5.',
            'skillsRating.required' => 'La calificación de habilidades es obligatoria.',
            'skillsRating.between' => 'La calificación de habilidades debe estar entre 1 y 5.',
            'attendanceRating.required' => 'La calificación de asistencia es obligatoria.',
            'attendanceRating.between' => 'La calificación de asistencia debe estar entre 1 y 5.',
            'comments.string' => 'Los comentarios deben ser texto.',
            'employeeComments.string' => 'Los comentarios del empleado deben ser texto.',
            'status.in' => 'El estado debe ser uno de: draft, submitted, reviewed, acknowledged.',
            'employee.required' => 'El empleado es obligatorio.',
            'reviewer.required' => 'El evaluador es obligatorio.',
        ];
    }
}
