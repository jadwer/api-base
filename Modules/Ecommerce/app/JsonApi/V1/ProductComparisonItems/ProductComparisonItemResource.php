<?php

namespace Modules\Ecommerce\JsonApi\V1\ProductComparisonItems;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class ProductComparisonItemResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'comparisonId' => $this->comparison_id,
            'productId' => $this->product_id,
            'position' => $this->position,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
            'comparison' => $this->relation('comparison'),
            'product' => $this->relation('product'),
        ];
    }
}
