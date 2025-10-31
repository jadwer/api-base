<?php

namespace Modules\HR\JsonApi\V1\Departments;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

class DepartmentResource extends JsonApiResource
{
    /**
     * Get the resource's attributes.
     *
     * @param Request|null $request
     * @return iterable
     */
    public function attributes($request): iterable
    {
        return [
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'isActive' => $this->resource->is_active,
            'createdAt' => $this->resource->created_at,
            'updatedAt' => $this->resource->updated_at,
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
            $this->relation('manager'),
            $this->relation('employees'),
            $this->relation('positions'),
        ];
    }
}
