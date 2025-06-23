<?php

namespace Modules\Audit\JsonApi\V1\Audits;

use App\Models\Audit;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

/**
 * @property Audit $resource
 */
class AuditResource extends JsonApiResource
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
            'event' => $this->event,
            'userId' => $this->causer_id,
            'auditableType' => $this->subject_type,
            'auditableId' => $this->subject_id,
            'oldValues' => $this->properties['old'] ?? null,
            'newValues' => $this->properties['attributes'] ?? null,
            'ipAddress' => $this->properties['ip_address'] ?? null,
            'userAgent' => $this->properties['user_agent'] ?? null,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
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
            // @TODO
        ];
    }

}
