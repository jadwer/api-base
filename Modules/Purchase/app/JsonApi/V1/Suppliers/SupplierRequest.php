<?php

namespace Modules\Purchase\JsonApi\V1\Suppliers;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class SupplierRequest extends ResourceRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $supplier = $this->model();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('suppliers', 'email')->ignore($supplier),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'rfc' => [
                'nullable',
                'string',
                'max:13',
                Rule::unique('suppliers', 'rfc')->ignore($supplier),
            ],
            'isActive' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del proveedor es obligatorio.',
            'email.email' => 'El email debe tener un formato válido.',
            'email.unique' => 'Este email ya está registrado para otro proveedor.',
            'rfc.unique' => 'Este RFC ya está registrado para otro proveedor.',
            'phone.max' => 'El teléfono no puede tener más de 20 caracteres.',
            'address.max' => 'La dirección no puede tener más de 500 caracteres.',
        ];
    }

    /**
     * Get default values for the request.
     */
    public function withDefaults(): array
    {
        return [
            'isActive' => true,
        ];
    }
}
