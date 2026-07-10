<?php

namespace Modules\Commissions\JsonApi\V1\Commissions;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

/**
 * Commissions are a read-only JSON:API resource (index/show). Writes happen
 * through the custom endpoints (mark-paid, pay-batch) and the system
 * observers/listeners, so there are no writable attributes here.
 */
class CommissionRequest extends ResourceRequest
{
    public function rules(): array
    {
        return [];
    }
}
