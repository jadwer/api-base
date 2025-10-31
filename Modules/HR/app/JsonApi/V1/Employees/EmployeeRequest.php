<?php

namespace Modules\HR\JsonApi\V1\Employees;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class EmployeeRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     */
    public function rules(): array
    {
        $employeeId = $this->route('employee');

        return [
            'employeeCode' => [
                'required',
                'string',
                'max:50',
                JsonApiRule::unique('employees', 'employee_code')->ignore($employeeId),
            ],
            'firstName' => [
                'required',
                'string',
                'max:100',
            ],
            'lastName' => [
                'required',
                'string',
                'max:100',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                JsonApiRule::unique('employees', 'email')->ignore($employeeId),
            ],
            'phone' => [
                'nullable',
                'string',
                'max:50',
            ],
            'hireDate' => [
                'required',
                'date',
            ],
            'birthDate' => [
                'nullable',
                'date',
                'before:today',
            ],
            'salary' => [
                'required',
                'numeric',
                'min:0',
            ],
            'status' => [
                'required',
                'string',
                'in:active,on_leave,suspended,terminated',
            ],
            'terminationDate' => [
                'nullable',
                'date',
                'after_or_equal:hireDate',
            ],
            'terminationReason' => [
                'nullable',
                'string',
            ],
            'address' => [
                'nullable',
                'string',
            ],
            'emergencyContactName' => [
                'nullable',
                'string',
                'max:100',
            ],
            'emergencyContactPhone' => [
                'nullable',
                'string',
                'max:50',
            ],
            'department' => [
                'required',
                JsonApiRule::toOne(),
            ],
            'position' => [
                'required',
                JsonApiRule::toOne(),
            ],
            'user' => [
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
            'employeeCode.required' => 'El código de empleado es obligatorio.',
            'employeeCode.string' => 'El código de empleado debe ser un texto.',
            'employeeCode.max' => 'El código de empleado no puede exceder 50 caracteres.',
            'employeeCode.unique' => 'Ya existe un empleado con este código.',
            'firstName.required' => 'El nombre es obligatorio.',
            'firstName.string' => 'El nombre debe ser un texto.',
            'firstName.max' => 'El nombre no puede exceder 100 caracteres.',
            'lastName.required' => 'El apellido es obligatorio.',
            'lastName.string' => 'El apellido debe ser un texto.',
            'lastName.max' => 'El apellido no puede exceder 100 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico debe ser una dirección válida.',
            'email.max' => 'El correo electrónico no puede exceder 255 caracteres.',
            'email.unique' => 'Ya existe un empleado con este correo electrónico.',
            'phone.string' => 'El teléfono debe ser un texto.',
            'phone.max' => 'El teléfono no puede exceder 50 caracteres.',
            'hireDate.required' => 'La fecha de contratación es obligatoria.',
            'hireDate.date' => 'La fecha de contratación debe ser una fecha válida.',
            'birthDate.date' => 'La fecha de nacimiento debe ser una fecha válida.',
            'birthDate.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
            'salary.required' => 'El salario es obligatorio.',
            'salary.numeric' => 'El salario debe ser un número.',
            'salary.min' => 'El salario no puede ser negativo.',
            'status.required' => 'El estado es obligatorio.',
            'status.string' => 'El estado debe ser un texto.',
            'status.in' => 'El estado debe ser uno de: active, on_leave, suspended, terminated.',
            'terminationDate.date' => 'La fecha de terminación debe ser una fecha válida.',
            'terminationDate.after_or_equal' => 'La fecha de terminación debe ser posterior o igual a la fecha de contratación.',
            'terminationReason.string' => 'La razón de terminación debe ser un texto.',
            'address.string' => 'La dirección debe ser un texto.',
            'emergencyContactName.string' => 'El nombre del contacto de emergencia debe ser un texto.',
            'emergencyContactName.max' => 'El nombre del contacto de emergencia no puede exceder 100 caracteres.',
            'emergencyContactPhone.string' => 'El teléfono del contacto de emergencia debe ser un texto.',
            'emergencyContactPhone.max' => 'El teléfono del contacto de emergencia no puede exceder 50 caracteres.',
            'department.required' => 'El departamento es obligatorio.',
            'department.to_one' => 'El departamento debe ser una relación válida.',
            'position.required' => 'El puesto es obligatorio.',
            'position.to_one' => 'El puesto debe ser una relación válida.',
            'user.to_one' => 'El usuario debe ser una relación válida.',
        ];
    }
}
