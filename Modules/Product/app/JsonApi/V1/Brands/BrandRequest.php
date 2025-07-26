<?php

namespace Modules\Product\JsonApi\V1\Brands;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class BrandRequest extends ResourceRequest
{
    public function rules(): array
    {
        $brand = $this->model();

        return [
            'name' => [
                'required', 
                'string', 
                'max:255',
                Rule::unique('brands', 'name')->ignore($brand),
            ],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
