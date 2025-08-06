<?php

namespace Modules\Ecommerce\JsonApi\V1\Coupons;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class CouponResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'couponType' => $this->type,
            'value' => $this->value,
            'minAmount' => $this->min_amount,
            'maxAmount' => $this->max_amount,
            'maxUses' => $this->max_uses,
            'usedCount' => $this->used_count,
            'startsAt' => $this->starts_at,
            'expiresAt' => $this->expires_at,
            'isActive' => $this->is_active,
            'customerIds' => $this->customer_ids,
            'productIds' => $this->product_ids,
            'categoryIds' => $this->category_ids,
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
