<?php

namespace Modules\Accounting\JsonApi\V1\ExchangeRatePolicies;

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
            // Barrido Paquete B 2026-08-31: el Resource manual pisa al
            // Schema; todo campo del Schema debe estar aqui o el API
            // guarda pero nunca lo devuelve.
            'metadata' => $this->metadata,
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
