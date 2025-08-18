<?php

namespace Modules\Contacts\JsonApi\V1\ContactAddresses;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class ContactAddressResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'contactId' => $this->contact_id,
            'addressType' => $this->address_type,
            'addressLine1' => $this->address_line_1,
            'addressLine2' => $this->address_line_2,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'postalCode' => $this->postal_code,
            'isDefault' => $this->is_default,
            "createdAt" => $this->created_at,
            "updatedAt" => $this->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
            'contact' => $this->relation('contact'),
            'contacts' => $this->relation('contacts'),
        ];
    }
}
