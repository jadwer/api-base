<?php

namespace Modules\Finance\JsonApi\V1\PaymentMethods;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class PaymentMethodResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'requiresReference' => $this->requires_reference,
            'isActive' => $this->is_active,
            "createdAt" => $this->created_at,
            "updatedAt" => $this->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
            'payment' => $this->relation('payment'),
        ];
    }
}
