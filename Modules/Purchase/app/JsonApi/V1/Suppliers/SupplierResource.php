<?php

namespace Modules\Purchase\JsonApi\V1\Suppliers;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

class SupplierResource extends JsonApiResource
{
    /**
     * Get the resource's attributes.
     */
    public function attributes($request): iterable
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'rfc' => $this->rfc,
            'isActive' => $this->is_active,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }

    /**
     * Get the resource's relationships.
     *
     * @param Request|null $request
     * @return iterable
     */
    public function relationships($request): iterable
    {
        return [
            $this->relation('purchaseOrders'),
        ];
    }
}
