<?php

namespace Modules\CRM\JsonApi\V1\Leads;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

class LeadResource extends JsonApiResource
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
            'title' => $this->title,
            'source' => $this->source,
            'status' => $this->status,
            'rating' => $this->rating,
            'companyName' => $this->company_name,
            'contactPerson' => $this->contact_person,
            'email' => $this->email,
            'phone' => $this->phone,
            'estimatedValue' => $this->estimated_value,
            'estimatedCloseDate' => $this->estimated_close_date,
            'convertedAt' => $this->converted_at,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
