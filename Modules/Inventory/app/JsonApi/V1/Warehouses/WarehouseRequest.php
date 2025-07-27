<?php

namespace Modules\Inventory\JsonApi\V1\Warehouses;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class WarehouseRequest extends ResourceRequest
{
    public function rules(): array
    {
        $warehouse = $this->model();

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('warehouses', 'slug')->ignore($warehouse),
            ],
            'description' => ['nullable', 'string'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('warehouses', 'code')->ignore($warehouse),
            ],
            'warehouseType' => [
                'required',
                'string',
                Rule::in(['main', 'secondary', 'distribution', 'returns']),
            ],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'postalCode' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'managerName' => ['nullable', 'string', 'max:255'],
            'maxCapacity' => ['nullable', 'numeric', 'min:0'],
            'capacityUnit' => ['nullable', 'string', 'max:10'],
            'operatingHours' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
            'isActive' => ['sometimes', 'boolean'],
        ];
    }

    public function withDefaults(): array
    {
        return [
            'isActive' => true,
            'warehouseType' => 'main',
            'capacityUnit' => 'm3',
        ];
    }
}
