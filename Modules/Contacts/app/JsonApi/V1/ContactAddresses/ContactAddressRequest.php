<?php

namespace Modules\Contacts\JsonApi\V1\ContactAddresses;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class ContactAddressRequest extends ResourceRequest
{
    public function rules(): array
    {
        $contactaddress = $this->model();
        
        return [
            'contactId' => ['required', 'integer'],
            'addressType' => ['nullable', 'string', 'max:255'],
            'addressLine1' => ['nullable', 'string', 'max:255'],
            'addressLine2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'postalCode' => ['nullable', 'string', 'max:255'],
            'isDefault' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'contactId.integer' => 'El campo Contact id debe ser un número entero.',
            'addressType.string' => 'El campo Type debe ser texto.',
            'addressType.max' => 'El campo Type no puede tener más de 255 caracteres.',
            'addressLine1.string' => 'El campo Address line 1 debe ser texto.',
            'addressLine1.max' => 'El campo Address line 1 no puede tener más de 255 caracteres.',
            'addressLine2.string' => 'El campo Address line 2 debe ser texto.',
            'addressLine2.max' => 'El campo Address line 2 no puede tener más de 255 caracteres.',
            'city.string' => 'El campo City debe ser texto.',
            'city.max' => 'El campo City no puede tener más de 255 caracteres.',
            'state.string' => 'El campo State debe ser texto.',
            'state.max' => 'El campo State no puede tener más de 255 caracteres.',
            'country.string' => 'El campo Country debe ser texto.',
            'country.max' => 'El campo Country no puede tener más de 255 caracteres.',
            'postalCode.string' => 'El campo Postal code debe ser texto.',
            'postalCode.max' => 'El campo Postal code no puede tener más de 255 caracteres.',
            'isDefault.boolean' => 'El campo Is default debe ser verdadero o falso.',
        ];
    }
}
