<?php

namespace Modules\Product\JsonApi\V1\Products;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class ProductRequest extends ResourceRequest
{
    public function rules(): array
    {
        $product = $this->model();

        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'required',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->ignore($product),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'fullDescription' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'iva' => ['required', 'boolean'],
            'imgPath' => ['nullable', 'string'],
            'datasheetPath' => ['nullable', 'string'],
            'unit' => JsonApiRule::toOne(),
            'category' => JsonApiRule::toOne(),
            'brand' => JsonApiRule::toOne(),
        ];
    }

    public function withDefaults(): array
    {
        return [
            'iva' => false,
        ];
    }
}
