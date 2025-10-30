<?php

namespace Modules\Ecommerce\JsonApi\V1\Wishlists;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

class WishlistResource extends JsonApiResource
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
            'userId' => $this->user_id,
            'name' => $this->name,
            'isDefault' => $this->is_default,
            'isPublic' => $this->is_public,
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
            $this->relation('user'),
            $this->relation('items'),
            $this->relation('products'),
        ];
    }
}
