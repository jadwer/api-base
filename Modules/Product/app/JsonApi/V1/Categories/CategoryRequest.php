<?php

namespace Modules\Product\JsonApi\V1\Categories;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class CategoryRequest extends ResourceRequest
{
    public function rules(): array
    {
        $category = $this->model();

        return [
            'name' => [
                'required', 
                'string', 
                'max:255',
                Rule::unique('categories', 'name')->ignore($category),
            ],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
