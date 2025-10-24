<?php

namespace Modules\Accounting\JsonApi\V1\ExchangeRatePolicys;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class ExchangeRatePolicyResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'companyId' => $this->company_id,
            'currency' => $this->currency,
            'source' => $this->source,
            'scope' => $this->scope,
            'maxAgeDays' => $this->max_age_days,
            'tolerancePercentage' => $this->tolerance_percentage,
            'requireApprovalOver' => $this->require_approval_over,
            'isActive' => $this->is_active,
            "createdAt" => $this->created_at,
            "updatedAt" => $this->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [

        ];
    }
}
