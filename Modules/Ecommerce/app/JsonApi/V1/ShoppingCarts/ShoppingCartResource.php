<?php

namespace Modules\Ecommerce\JsonApi\V1\ShoppingCarts;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class ShoppingCartResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'sessionId' => $this->session_id,
            'userId' => $this->user_id,
            'status' => $this->status,
            'expiresAt' => $this->expires_at,
            'totalAmount' => $this->total_amount,
            'currency' => $this->currency,
            'couponCode' => $this->coupon_code,
            'discountAmount' => $this->discount_amount,
            'taxAmount' => $this->tax_amount,
            'shippingAmount' => $this->shipping_amount,
            'notes' => $this->notes,
            "createdAt" => $this->created_at,
            "updatedAt" => $this->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
            'cartItems' => $this->relation('cartItems'),
            'user' => $this->relation('user'),
        ];
    }
}
