<?php

namespace Modules\Ecommerce\JsonApi\V1\ProductComparisonItems;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class ProductComparisonItemRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     */
    public function rules(): array
    {
        return [
            'comparisonId' => ['required', 'integer', 'exists:product_comparisons,id'],
            'productId' => ['required', 'integer', 'exists:products,id'],
            'position' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'comparisonId.required' => 'La comparación es obligatoria.',
            'comparisonId.exists' => 'La comparación no existe.',
            'productId.required' => 'El producto es obligatorio.',
            'productId.exists' => 'El producto no existe.',
            'position.integer' => 'La posición debe ser un número entero.',
            'position.min' => 'La posición debe ser mayor o igual a 0.',
        ];
    }
}
