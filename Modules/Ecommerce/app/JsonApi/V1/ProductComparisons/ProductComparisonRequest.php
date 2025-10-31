<?php

namespace Modules\Ecommerce\JsonApi\V1\ProductComparisons;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class ProductComparisonRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'isPublic' => ['sometimes', 'boolean'],
            'user' => JsonApiRule::toOne(),
            'items' => JsonApiRule::toMany(),
        ];
    }
}
