<?php

namespace Modules\HR\JsonApi\V1\Attendances;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

class AttendanceResource extends JsonApiResource
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
            'date' => $this->resource->date,
            'checkIn' => $this->resource->check_in,
            'checkOut' => $this->resource->check_out,
            'hoursWorked' => $this->resource->hours_worked,
            'overtimeHours' => $this->resource->overtime_hours,
            'status' => $this->resource->status,
            'notes' => $this->resource->notes,
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
        ];
    }
}
