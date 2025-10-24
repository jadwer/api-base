<?php

namespace Modules\Accounting\JsonApi\V1\AccountMappings;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class AccountMappingResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'companyId' => $this->company_id,
            'mappingType' => $this->mapping_type,
            'accountId' => $this->account_id,
            'version' => $this->version,
            'effectiveFrom' => $this->effective_from,
            'effectiveTo' => $this->effective_to,
            'isActive' => $this->is_active,
            'createdById' => $this->created_by_id,
            'notes' => $this->notes,
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
