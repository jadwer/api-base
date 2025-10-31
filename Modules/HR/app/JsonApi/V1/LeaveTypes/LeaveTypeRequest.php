<?php

namespace Modules\HR\JsonApi\V1\LeaveTypes;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class LeaveTypeRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     *
     * @return array
     */
    public function rules(): array
    {
        $leaveTypeId = $this->route('leave_type');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('leave_types', 'code')->ignore($leaveTypeId),
            ],
            'description' => ['nullable', 'string'],
            'daysAllowed' => ['required', 'integer', 'min:0'],
            'requiresApproval' => ['sometimes', 'boolean'],
            'paid' => ['sometimes', 'boolean'],
            'active' => ['sometimes', 'boolean'],
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
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser texto.',
            'name.max' => 'El nombre no debe exceder 255 caracteres.',
            'code.required' => 'El código es obligatorio.',
            'code.string' => 'El código debe ser texto.',
            'code.max' => 'El código no debe exceder 20 caracteres.',
            'code.unique' => 'Este código ya está en uso.',
            'description.string' => 'La descripción debe ser texto.',
            'daysAllowed.required' => 'Los días permitidos son obligatorios.',
            'daysAllowed.integer' => 'Los días permitidos deben ser un número entero.',
            'daysAllowed.min' => 'Los días permitidos no pueden ser negativos.',
            'requiresApproval.boolean' => 'El campo requiere aprobación debe ser verdadero o falso.',
            'paid.boolean' => 'El campo pagado debe ser verdadero o falso.',
            'active.boolean' => 'El campo activo debe ser verdadero o falso.',
        ];
    }
}
