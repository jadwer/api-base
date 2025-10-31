<?php

namespace Modules\HR\JsonApi\V1\Positions;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

class PositionResource extends JsonApiResource
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
            'title' => $this->resource->title,
            'description' => $this->resource->description,
            'level' => $this->resource->level,
            'minSalary' => $this->resource->min_salary,
            'maxSalary' => $this->resource->max_salary,
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
            $this->relation('department'),
            $this->relation('employees'),
        ];
    }
}
