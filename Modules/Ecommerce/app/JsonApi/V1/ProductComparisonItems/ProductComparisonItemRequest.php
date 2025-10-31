<?php

namespace Modules\Ecommerce\JsonApi\V1\ProductComparisonItems;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class ProductComparisonItemRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     */
    public function rules(): array
    {
        return [
            'position' => ['sometimes', 'integer', 'min:0'],
            'comparison' => JsonApiRule::toOne()->required(),
            'product' => JsonApiRule::toOne()->required(),
        ];
    }
}
