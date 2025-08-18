<?php

namespace Modules\Contacts\JsonApi\V1\ContactPeople;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class ContactPersonRequest extends ResourceRequest
{
    public function rules(): array
    {
        $contactperson = $this->model();
        
        return [
            'contact_id' => ['nullable', 'integer'],
            'name' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255', 'email'],
            'phone' => ['nullable', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:255'],
            'is_primary' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'contact_id.integer' => 'El campo Contact id debe ser un número entero.',
            'name.string' => 'El campo Name debe ser texto.',
            'name.max' => 'El campo Name no puede tener más de 255 caracteres.',
            'position.string' => 'El campo Position debe ser texto.',
            'position.max' => 'El campo Position no puede tener más de 255 caracteres.',
            'department.string' => 'El campo Department debe ser texto.',
            'department.max' => 'El campo Department no puede tener más de 255 caracteres.',
            'email.string' => 'El campo Email debe ser texto.',
            'email.max' => 'El campo Email no puede tener más de 255 caracteres.',
            'email.email' => 'El formato del email no es válido.',
            'phone.string' => 'El campo Phone debe ser texto.',
            'phone.max' => 'El campo Phone no puede tener más de 255 caracteres.',
            'mobile.string' => 'El campo Mobile debe ser texto.',
            'mobile.max' => 'El campo Mobile no puede tener más de 255 caracteres.',
            'is_primary.boolean' => 'El campo Is primary debe ser verdadero o falso.',
        ];
    }
}
