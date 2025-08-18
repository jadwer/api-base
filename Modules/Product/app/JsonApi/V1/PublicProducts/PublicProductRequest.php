<?php

namespace Modules\Product\JsonApi\V1\PublicProducts;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class PublicProductRequest extends ResourceRequest
{
    /**
     * Public catalog is read-only, no validation rules needed.
     */
    public function rules(): array
    {
        return [];
    }
}