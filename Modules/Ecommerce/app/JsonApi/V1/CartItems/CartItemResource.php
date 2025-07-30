<?php

namespace Modules\Ecommerce\JsonApi\V1\CartItems;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class CartItemResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'shoppingCartId' => $this->shopping_cart_id,
            'productId' => $this->product_id,
            'quantity' => $this->quantity,
            'unitPrice' => $this->unit_price,
            'originalPrice' => $this->original_price,
            'discountPercent' => $this->discount_percent,
            'discountAmount' => $this->discount_amount,
            'subtotal' => $this->subtotal,
            'taxRate' => $this->tax_rate,
            'taxAmount' => $this->tax_amount,
            'total' => $this->total,
            "createdAt" => $this->created_at,
            "updatedAt" => $this->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
            $this->relationshipData('shoppingCart'),
            $this->relationshipData('product'),
        ];
    }
}
