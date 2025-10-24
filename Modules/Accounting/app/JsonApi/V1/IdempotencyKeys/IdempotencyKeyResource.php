<?php

namespace Modules\Accounting\JsonApi\V1\IdempotencyKeys;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class IdempotencyKeyResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'companyId' => $this->company_id,
            'userId' => $this->user_id,
            'endpoint' => $this->endpoint,
            'idempotencyKey' => $this->idempotency_key,
            'requestHash' => $this->request_hash,
            'responseData' => $this->response_data,
            'status' => $this->status,
            'expiresAt' => $this->expires_at,
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
