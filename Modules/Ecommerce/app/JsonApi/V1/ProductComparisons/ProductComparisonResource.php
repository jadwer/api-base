<?php

namespace Modules\Ecommerce\JsonApi\V1\ProductComparisons;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class ProductComparisonResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'name' => $this->name,
            'isPublic' => $this->is_public,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
            'user' => $this->relation('user'),
            'items' => $this->relation('items'),
        ];
    }
}
