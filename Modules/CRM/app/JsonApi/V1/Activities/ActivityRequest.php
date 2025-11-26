<?php

namespace Modules\CRM\JsonApi\V1\Activities;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class ActivityRequest extends ResourceRequest
{
    public function rules(): array
    {
        return [
            'activityType' => ['required', Rule::in(['call', 'email', 'meeting', 'note', 'task'])],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'activityDate' => ['sometimes', 'nullable', 'date'],
            'duration' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'outcome' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['scheduled', 'completed', 'cancelled', 'pending'])],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'user' => 'required',
            'lead' => 'nullable',
            'campaign' => 'nullable',
        ];
    }

    public function messages(): array
    {
        return [
            'activityType.required' => 'El tipo de actividad es obligatorio.',
            'activityType.in' => 'El tipo debe ser: call, email, meeting, note, o task.',

            'subject.required' => 'El asunto es obligatorio.',
            'subject.string' => 'El asunto debe ser una cadena de texto.',
            'subject.max' => 'El asunto no debe exceder 255 caracteres.',

            'description.string' => 'La descripción debe ser una cadena de texto.',

            'activityDate.date' => 'La fecha de actividad debe ser una fecha válida.',

            'duration.integer' => 'La duración debe ser un número entero.',
            'duration.min' => 'La duración no puede ser negativa.',

            'outcome.string' => 'El resultado debe ser una cadena de texto.',
            'outcome.max' => 'El resultado no debe exceder 255 caracteres.',

            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado debe ser: scheduled, completed, cancelled, o pending.',

            'metadata.array' => 'Los metadatos deben ser un arreglo.',
        ];
    }
}
