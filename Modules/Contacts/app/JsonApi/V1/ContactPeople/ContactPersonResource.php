<?php

namespace Modules\Contacts\JsonApi\V1\ContactPeople;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class ContactPersonResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'contactId' => $this->contact_id,
            'name' => $this->name,
            'position' => $this->position,
            'department' => $this->department,
            'email' => $this->email,
            'phone' => $this->phone,
            'mobile' => $this->mobile,
            'isPrimary' => $this->is_primary,
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
