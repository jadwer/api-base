<?php

namespace Modules\Product\JsonApi\V1\VariantAttributes;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

/**
 * PR-M003: JSON:API Request validation for VariantAttribute.
 */
class VariantAttributeRequest extends ResourceRequest
{
    public function rules(): array
    {
        $model = $this->model();

        return [
            'name' => [
                $this->isCreating() ? 'required' : 'sometimes',
                'string',
                'max:100',
            ],
            'code' => [
                $this->isCreating() ? 'required' : 'sometimes',
                'string',
                'max:50',
                Rule::unique('variant_attributes', 'code')->ignore($model),
            ],
            'description' => ['nullable', 'string'],
            'isActive' => ['sometimes', 'boolean'],
            'sortOrder' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del atributo es obligatorio.',
            'name.max' => 'El nombre no puede exceder :max caracteres.',
            'code.required' => 'El código del atributo es obligatorio.',
            'code.unique' => 'Este código de atributo ya existe.',
            'code.max' => 'El código no puede exceder :max caracteres.',
            'sortOrder.min' => 'El orden debe ser un número positivo.',
        ];
    }
}
