<?php

namespace Modules\Contacts\JsonApi\V1\Contacts;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class ContactRequest extends ResourceRequest
{
    public function rules(): array
    {
        $contact = $this->model();
        $isCreating = $this->isCreating();
        
        $rules = [
            'contactType' => ['required', Rule::in(['person', 'company'])],
            'name' => ['required', 'string', 'max:255'],
            'legalName' => ['nullable', 'string', 'max:255'],
            'taxId' => [
                'nullable',
                'string',
                'max:13',
                'regex:/^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$/',
                Rule::unique('contacts', 'tax_id')->ignore($contact?->id),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'website' => ['nullable', 'url', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
            'isCustomer' => ['boolean'],
            'isSupplier' => ['boolean'],
            'creditLimit' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'currentCredit' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'classification' => ['nullable', Rule::in(['premium', 'standard', 'basic'])],
            'paymentTerms' => ['nullable', 'integer', 'min:0', 'max:365'],
            'notes' => ['nullable', 'string', 'max:65535'],
            'metadata' => ['nullable', 'array'],
        ];

        // For updates, make some fields optional  
        if (!$isCreating) {
            $rules['contactType'] = ['sometimes', Rule::in(['person', 'company'])];
            $rules['name'] = ['sometimes', 'string', 'max:255'];
            $rules['status'] = ['sometimes', Rule::in(['active', 'inactive', 'suspended'])];
        }

        return $rules;
    }

    // Moved complex business logic to the model boot() method
    // This keeps the Request class focused on basic validation

    public function messages(): array
    {
        return [
            'contactType.required' => 'El tipo de contacto es obligatorio.',
            'contactType.in' => 'El tipo de contacto debe ser persona o empresa.',
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser texto.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
            'legalName.string' => 'La razón social debe ser texto.',
            'legalName.max' => 'La razón social no puede tener más de 255 caracteres.',
            'taxId.string' => 'El RFC debe ser texto.',
            'taxId.max' => 'El RFC no puede tener más de 13 caracteres.',
            'taxId.unique' => 'El RFC ya está registrado para otro contacto.',
            'taxId.regex' => 'El formato del RFC no es válido.',
            'email.email' => 'El formato del email no es válido.',
            'email.max' => 'El email no puede tener más de 255 caracteres.',
            'phone.string' => 'El teléfono debe ser texto.',
            'phone.max' => 'El teléfono no puede tener más de 20 caracteres.',
            'website.url' => 'El sitio web debe ser una URL válida.',
            'website.max' => 'El sitio web no puede tener más de 255 caracteres.',
            'status.required' => 'El estatus es obligatorio.',
            'status.in' => 'El estatus debe ser activo, inactivo o suspendido.',
            'isCustomer.boolean' => 'El campo cliente debe ser verdadero o falso.',
            'isSupplier.boolean' => 'El campo proveedor debe ser verdadero o falso.',
            'creditLimit.numeric' => 'El límite de crédito debe ser un número.',
            'creditLimit.min' => 'El límite de crédito no puede ser negativo.',
            'currentCredit.numeric' => 'El crédito actual debe ser un número.',
            'currentCredit.min' => 'El crédito actual no puede ser negativo.',
            'classification.in' => 'La clasificación debe ser premium, standard o basic.',
            'paymentTerms.integer' => 'Los días de pago deben ser un número entero.',
            'paymentTerms.min' => 'Los días de pago no pueden ser negativos.',
            'paymentTerms.max' => 'Los días de pago no pueden ser mayores a 365.',
            'notes.string' => 'Las notas deben ser texto.',
            'metadata.array' => 'Los metadatos deben ser un arreglo.',
        ];
    }
}
