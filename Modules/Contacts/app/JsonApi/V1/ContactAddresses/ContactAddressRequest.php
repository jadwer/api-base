<?php

namespace Modules\Contacts\JsonApi\V1\ContactAddresses;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class ContactAddressRequest extends ResourceRequest
{
    public function rules(): array
    {
        $isCreating = $this->isCreating();

        // Una sola direccion FISCAL por contacto (peticion cliente
        // 2026-08-25: siempre debe ser claro cual es la fiscal). Solo se
        // valida cuando el payload declara addressType fiscal.
        $contactId = $this->input('data.attributes.contactId') ?? $this->model()?->contact_id;
        $singleFiscal = Rule::unique('contact_addresses', 'address_type')
            ->where('contact_id', $contactId)
            ->where('address_type', 'fiscal');
        if ($this->model()) {
            $singleFiscal = $singleFiscal->ignore($this->model()->getKey());
        }

        return [
            'contactId' => [$isCreating ? 'required' : 'sometimes', 'integer'],
            'addressType' => [
                'nullable', 'string', 'max:255',
                Rule::in(['billing', 'shipping', 'fiscal', 'other', 'both']),
                Rule::when(fn () => $this->input('data.attributes.addressType') === 'fiscal', [$singleFiscal]),
            ],
            'addressLine1' => ['nullable', 'string', 'max:255'],
            'addressLine2' => ['nullable', 'string', 'max:255'],
            // Campos SAT: texto libre laxo A PROPOSITO. El SAT no tiene
            // campos manzana/lote ("Mz 3 Lt 3" va en calle o numero
            // exterior), asi que nada de validacion numerica aqui.
            'street' => ['nullable', 'string', 'max:255'],
            'exteriorNumber' => ['nullable', 'string', 'max:50'],
            'interiorNumber' => ['nullable', 'string', 'max:50'],
            'neighborhood' => ['nullable', 'string', 'max:255'],
            'municipality' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
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
            'addressType.in' => 'El tipo de direccion debe ser: fiscal, shipping, billing, other o both.',
            'addressType.unique' => 'Este contacto ya tiene una direccion fiscal; edita la existente.',
            'street.max' => 'La calle no puede tener más de 255 caracteres.',
            'exteriorNumber.max' => 'El número exterior no puede tener más de 50 caracteres.',
            'interiorNumber.max' => 'El número interior no puede tener más de 50 caracteres.',
            'neighborhood.max' => 'La colonia no puede tener más de 255 caracteres.',
            'municipality.max' => 'El municipio o alcaldía no puede tener más de 255 caracteres.',
            'reference.max' => 'La referencia no puede tener más de 255 caracteres.',
        ];
    }
}
