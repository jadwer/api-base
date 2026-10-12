<?php

namespace Modules\Contacts\JsonApi\V1\ContactPeople;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class ContactPersonRequest extends ResourceRequest
{
    public function rules(): array
    {
        $contactperson = $this->model();
        $isUpdate = $this->isMethod('patch');
        
        return [
            'contactId' => $isUpdate ? ['sometimes', 'integer', 'exists:contacts,id'] : ['required', 'integer', 'exists:contacts,id'],
            'name' => $isUpdate ? ['sometimes', 'string', 'max:255'] : ['required', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255', 'email'],
            'phone' => ['nullable', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:255'],
            'isPrimary' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'contact_id.integer' => 'El campo contacto debe ser un número entero.',
            'name.string' => 'El campo nombre debe ser texto.',
            'name.max' => 'El campo nombre no puede tener más de 255 caracteres.',
            'position.string' => 'El campo posición debe ser texto.',
            'position.max' => 'El campo posición no puede tener más de 255 caracteres.',
            'department.string' => 'El campo departamento debe ser texto.',
            'department.max' => 'El campo departamento no puede tener más de 255 caracteres.',
            'email.string' => 'El campo correo electrónico debe ser texto.',
            'email.max' => 'El campo correo electrónico no puede tener más de 255 caracteres.',
            'email.email' => 'El formato del email no es válido.',
            'phone.string' => 'El campo teléfono debe ser texto.',
            'phone.max' => 'El campo teléfono no puede tener más de 255 caracteres.',
            'mobile.string' => 'El campo celular debe ser texto.',
            'mobile.max' => 'El campo celular no puede tener más de 255 caracteres.',
            'is_primary.boolean' => 'El campo principal debe ser verdadero o falso.',
        ];
    }
}
