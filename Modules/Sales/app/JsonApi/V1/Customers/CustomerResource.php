<?php

namespace Modules\Sales\JsonApi\V1\Customers;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class CustomerResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'classification' => $this->classification,
            'credit_limit' => $this->credit_limit,
            'current_credit' => $this->current_credit,
            'is_active' => $this->is_active,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
