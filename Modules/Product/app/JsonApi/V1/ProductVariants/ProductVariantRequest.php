<?php

namespace Modules\Product\JsonApi\V1\ProductVariants;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

/**
 * PR-M003: JSON:API Request validation for ProductVariant.
 */
class ProductVariantRequest extends ResourceRequest
{
    public function rules(): array
    {
        $model = $this->model();

        return [
            'sku' => [
                $this->isCreating() ? 'required' : 'sometimes',
                'string',
                'max:100',
                Rule::unique('product_variants', 'sku')->ignore($model),
            ],
            'name' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'barcode' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('product_variants', 'barcode')->ignore($model),
            ],
            'imgPath' => ['nullable', 'string', 'max:400'],
            'stockQuantity' => ['sometimes', 'integer', 'min:0'],
            'lowStockThreshold' => ['nullable', 'integer', 'min:0'],
            'isActive' => ['sometimes', 'boolean'],
            'isDefault' => ['sometimes', 'boolean'],
            'metadata' => ['nullable', 'array'],
            // Relationship validation
            'product' => [$this->isCreating() ? 'required' : 'sometimes', JsonApiRule::toOne()],
        ];
    }

    public function messages(): array
    {
        return [
            'product.required' => 'El producto es obligatorio.',
            'sku.required' => 'El SKU es obligatorio.',
            'sku.unique' => 'Este SKU ya está en uso.',
            'sku.max' => 'El SKU no puede exceder :max caracteres.',
            'name.max' => 'El nombre no puede exceder :max caracteres.',
            'price.numeric' => 'El precio debe ser un número.',
            'price.min' => 'El precio no puede ser negativo.',
            'cost.numeric' => 'El costo debe ser un número.',
            'cost.min' => 'El costo no puede ser negativo.',
            'weight.numeric' => 'El peso debe ser un número.',
            'weight.min' => 'El peso no puede ser negativo.',
            'barcode.unique' => 'Este código de barras ya está en uso.',
            'stockQuantity.min' => 'El stock no puede ser negativo.',
            'lowStockThreshold.min' => 'El umbral de stock bajo no puede ser negativo.',
        ];
    }
}
