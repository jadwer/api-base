<?php

namespace Modules\Product\JsonApi\V1\ProductImages;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class ProductImageRequest extends ResourceRequest
{
    public function rules(): array
    {
        return [
            'filePath' => ['required', 'string', 'max:400', 'not_regex:/\.\./'],
            'altText' => ['nullable', 'string', 'max:255'],
            'sortOrder' => ['sometimes', 'integer', 'min:0'],
            'isPrimary' => ['sometimes', 'boolean'],
            'product' => JsonApiRule::toOne(),
        ];
    }
}
