<?php

namespace Modules\CRM\JsonApi\V1\Leads;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class LeadRequest extends ResourceRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'source' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['new', 'contacted', 'qualified', 'unqualified', 'converted'])],
            'rating' => ['required', Rule::in(['hot', 'warm', 'cold'])],
            'companyName' => ['sometimes', 'nullable', 'string', 'max:255'],
            'contactPerson' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:255'],
            'estimatedValue' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'estimatedCloseDate' => ['sometimes', 'nullable', 'date'],
            'convertedAt' => ['sometimes', 'nullable', 'date'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'user' => 'required',
            'contact' => 'nullable',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'El título es obligatorio.',
            'title.string' => 'El título debe ser una cadena de texto.',
            'title.max' => 'El título no debe exceder 255 caracteres.',

            'source.string' => 'La fuente debe ser una cadena de texto.',
            'source.max' => 'La fuente no debe exceder 255 caracteres.',

            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado debe ser: new, contacted, qualified, unqualified, o converted.',

            'rating.required' => 'La calificación es obligatoria.',
            'rating.in' => 'La calificación debe ser: hot, warm, o cold.',

            'companyName.string' => 'El nombre de la empresa debe ser una cadena de texto.',
            'companyName.max' => 'El nombre de la empresa no debe exceder 255 caracteres.',

            'contactPerson.string' => 'El nombre de la persona de contacto debe ser una cadena de texto.',
            'contactPerson.max' => 'El nombre de la persona de contacto no debe exceder 255 caracteres.',

            'email.email' => 'El email debe ser una dirección válida.',
            'email.max' => 'El email no debe exceder 255 caracteres.',

            'phone.string' => 'El teléfono debe ser una cadena de texto.',
            'phone.max' => 'El teléfono no debe exceder 255 caracteres.',

            'estimatedValue.numeric' => 'El valor estimado debe ser un número.',
            'estimatedValue.min' => 'El valor estimado no puede ser negativo.',

            'estimatedCloseDate.date' => 'La fecha estimada de cierre debe ser una fecha válida.',

            'convertedAt.date' => 'La fecha de conversión debe ser una fecha válida.',

            'notes.string' => 'Las notas deben ser una cadena de texto.',

            'metadata.array' => 'Los metadatos deben ser un arreglo.',
        ];
    }
}
