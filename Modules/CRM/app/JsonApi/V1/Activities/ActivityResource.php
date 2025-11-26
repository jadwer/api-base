<?php

namespace Modules\CRM\JsonApi\V1\Activities;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

class ActivityResource extends JsonApiResource
{
    /**
     * Get the resource's attributes.
     *
     * @param  Request|null  $request
     * @return array
     */
    public function attributes($request): array
    {
        return [
            'activityType' => $this->activity_type,
            'subject' => $this->subject,
            'description' => $this->description,
            'activityDate' => $this->activity_date,
            'duration' => $this->duration,
            'outcome' => $this->outcome,
            'status' => $this->status,
            'metadata' => $this->metadata,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
