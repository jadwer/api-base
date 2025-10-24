<?php

namespace Modules\Accounting\JsonApi\V1\AuditLogs;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class AuditLogResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'companyId' => $this->company_id,
            'modelType' => $this->model_type,
            'modelId' => $this->model_id,
            'action' => $this->action,
            'userId' => $this->user_id,
            'changes' => $this->changes,
            'ipAddress' => $this->ip_address,
            'userAgent' => $this->user_agent,
            'sessionId' => $this->session_id,
            'payloadHash' => $this->payload_hash,
            'requiresRetention' => $this->requires_retention,
            'retentionUntil' => $this->retention_until,
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
