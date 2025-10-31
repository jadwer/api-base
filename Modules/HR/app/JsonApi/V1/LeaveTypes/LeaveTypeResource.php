<?php

namespace Modules\HR\JsonApi\V1\LeaveTypes;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

class LeaveTypeResource extends JsonApiResource
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
            'code' => $this->resource->code,
            'description' => $this->resource->description,
            'daysAllowed' => $this->resource->days_allowed,
            'requiresApproval' => $this->resource->requires_approval,
            'paid' => $this->resource->paid,
            'active' => $this->resource->active,
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
            $this->relation('leaves'),
        ];
    }
}
