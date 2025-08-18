<?php

namespace Modules\Contacts\JsonApi\V1\Contacts;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class ContactResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'type' => $this->type,
            'name' => $this->name,
            'legalName' => $this->legal_name,
            'taxId' => $this->tax_id,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'status' => $this->status,
            'isCustomer' => $this->is_customer,
            'isSupplier' => $this->is_supplier,
            'creditLimit' => $this->credit_limit,
            'currentCredit' => $this->current_credit,
            'classification' => $this->classification,
            'paymentTerms' => $this->payment_terms,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            "createdAt" => $this->created_at,
            "updatedAt" => $this->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
            'contactDocument' => $this->relation('contactDocument'),
            'contactDocuments' => $this->relation('contactDocuments'),
            'contactAddress' => $this->relation('contactAddress'),
            'contactAddresses' => $this->relation('contactAddresses'),
            'contactPerson' => $this->relation('contactPerson'),
            'contactPeople' => $this->relation('contactPeople'),
        ];
    }
}
