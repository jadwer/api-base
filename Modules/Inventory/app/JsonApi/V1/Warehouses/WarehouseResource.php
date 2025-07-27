<?php

namespace Modules\Inventory\JsonApi\V1\Warehouses;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class WarehouseResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'code' => $this->code,
            'warehouseType' => $this->warehouse_type,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'postalCode' => $this->postal_code,
            'phone' => $this->phone,
            'email' => $this->email,
            'managerName' => $this->manager_name,
            'maxCapacity' => $this->max_capacity,
            'capacityUnit' => $this->capacity_unit,
            'operatingHours' => $this->operatingHours,
            'metadata' => $this->metadata,
            'isActive' => $this->is_active,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
            'locations' => $this->relation('locations'),
            'stock' => $this->relation('stock'),
            'productBatches' => $this->relation('productBatches'),
        ];
    }
}
