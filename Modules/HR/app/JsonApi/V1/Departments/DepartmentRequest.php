<?php

namespace Modules\HR\JsonApi\V1\Departments;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class DepartmentRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     */
    public function rules(): array
    {
        $departmentId = $this->route('department');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                JsonApiRule::unique('departments', 'name')->ignore($departmentId),
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'isActive' => [
                'sometimes',
                'boolean',
            ],
            'manager' => [
                'nullable',
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
            'name.required' => 'El nombre del departamento es obligatorio.',
            'name.string' => 'El nombre del departamento debe ser un texto.',
            'name.max' => 'El nombre del departamento no puede exceder 100 caracteres.',
            'name.unique' => 'Ya existe un departamento con este nombre.',
            'description.string' => 'La descripción debe ser un texto.',
            'isActive.boolean' => 'El estado activo debe ser verdadero o falso.',
            'manager.to_one' => 'El gerente debe ser una relación válida.',
        ];
    }
}
