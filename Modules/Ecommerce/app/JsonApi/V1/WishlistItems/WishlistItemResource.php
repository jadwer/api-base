<?php

namespace Modules\Ecommerce\JsonApi\V1\WishlistItems;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

class WishlistItemResource extends JsonApiResource
{
    /**
     * Get the resource's attributes.
     *
     * @param Request|null $request
     * @return array
     */
    public function attributes($request): array
    {
        return [
            'wishlistId' => $this->wishlist_id,
            'productId' => $this->product_id,
            'quantity' => $this->quantity,
            'priority' => $this->priority,
            'notes' => $this->notes,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }

    /**
     * Get the resource's relationships.
     *
     * @param Request|null $request
     * @return array
     */
    public function relationships($request): array
    {
        return [
            $this->relation('wishlist'),
            $this->relation('product'),
        ];
    }
}
