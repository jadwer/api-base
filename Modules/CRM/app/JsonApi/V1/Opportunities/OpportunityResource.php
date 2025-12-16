<?php

namespace Modules\CRM\JsonApi\V1\Opportunities;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

class OpportunityResource extends JsonApiResource
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
            'name' => $this->name,
            'description' => $this->description,
            'amount' => $this->amount,
            'probability' => $this->probability,
            'expectedRevenue' => $this->expected_revenue,
            'actualRevenue' => $this->actual_revenue,
            'closeDate' => $this->close_date,
            'status' => $this->status,
            'stage' => $this->stage,
            'forecastCategory' => $this->forecast_category,
            'source' => $this->source,
            'nextStep' => $this->next_step,
            'lossReason' => $this->loss_reason,
            'wonAt' => $this->won_at,
            'lostAt' => $this->lost_at,
            'metadata' => $this->metadata,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
