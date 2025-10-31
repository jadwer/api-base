<?php

namespace Modules\HR\JsonApi\V1\Positions;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class PositionRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     */
    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:100',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'level' => [
                'required',
                'string',
                'in:entry,junior,mid,senior,lead,manager,director,executive',
            ],
            'minSalary' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'maxSalary' => [
                'nullable',
                'numeric',
                'min:0',
                'gte:minSalary',
            ],
            'isActive' => [
                'sometimes',
                'boolean',
            ],
            'department' => [
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
            'title.required' => 'El título del puesto es obligatorio.',
            'title.string' => 'El título del puesto debe ser un texto.',
            'title.max' => 'El título del puesto no puede exceder 100 caracteres.',
            'description.string' => 'La descripción debe ser un texto.',
            'level.required' => 'El nivel del puesto es obligatorio.',
            'level.string' => 'El nivel del puesto debe ser un texto.',
            'level.in' => 'El nivel del puesto debe ser uno de: entry, junior, mid, senior, lead, manager, director, executive.',
            'minSalary.numeric' => 'El salario mínimo debe ser un número.',
            'minSalary.min' => 'El salario mínimo no puede ser negativo.',
            'maxSalary.numeric' => 'El salario máximo debe ser un número.',
            'maxSalary.min' => 'El salario máximo no puede ser negativo.',
            'maxSalary.gte' => 'El salario máximo debe ser mayor o igual al salario mínimo.',
            'isActive.boolean' => 'El estado activo debe ser verdadero o falso.',
            'department.required' => 'El departamento es obligatorio.',
            'department.to_one' => 'El departamento debe ser una relación válida.',
        ];
    }
}
