<?php

namespace Modules\HR\JsonApi\V1\Leaves;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

class LeaveResource extends JsonApiResource
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
            'startDate' => $this->resource->start_date,
            'endDate' => $this->resource->end_date,
            'daysRequested' => $this->resource->days_requested,
            'status' => $this->resource->status,
            'reason' => $this->resource->reason,
            'notes' => $this->resource->notes,
            'approvedAt' => $this->resource->approved_at,
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
            $this->relation('employee'),
            $this->relation('leaveType'),
            $this->relation('approver'),
        ];
    }
}
