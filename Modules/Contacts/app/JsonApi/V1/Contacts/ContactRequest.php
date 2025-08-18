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
            'taxId' => ['nullable', 'string', 'max:13', 'regex:/^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$/'],
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
            'type.string' => 'El campo Type debe ser texto.',
            'type.max' => 'El campo Type no puede tener más de 255 caracteres.',
            'name.string' => 'El campo Name debe ser texto.',
            'name.max' => 'El campo Name no puede tener más de 255 caracteres.',
            'legal_name.string' => 'El campo Legal name debe ser texto.',
            'legal_name.max' => 'El campo Legal name no puede tener más de 255 caracteres.',
            'tax_id.string' => 'El campo Tax id debe ser texto.',
            'tax_id.max' => 'El campo Tax id no puede tener más de 255 caracteres.',
            'email.string' => 'El campo Email debe ser texto.',
            'email.max' => 'El campo Email no puede tener más de 255 caracteres.',
            'email.email' => 'El formato del email no es válido.',
            'phone.string' => 'El campo Phone debe ser texto.',
            'phone.max' => 'El campo Phone no puede tener más de 255 caracteres.',
            'website.string' => 'El campo Website debe ser texto.',
            'website.max' => 'El campo Website no puede tener más de 255 caracteres.',
            'status.string' => 'El campo Status debe ser texto.',
            'status.max' => 'El campo Status no puede tener más de 255 caracteres.',
            'is_customer.boolean' => 'El campo Is customer debe ser verdadero o falso.',
            'is_supplier.boolean' => 'El campo Is supplier debe ser verdadero o falso.',
            'classification.string' => 'El campo Classification debe ser texto.',
            'classification.max' => 'El campo Classification no puede tener más de 255 caracteres.',
            'payment_terms.integer' => 'El campo Payment terms debe ser un número entero.',
            'notes.string' => 'El campo Notes debe ser texto.',
            'metadata.array' => 'El campo Metadata debe ser un arreglo.',
        ];
    }
}
