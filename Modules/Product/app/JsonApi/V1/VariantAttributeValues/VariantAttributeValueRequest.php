<?php

namespace Modules\Product\JsonApi\V1\VariantAttributeValues;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

/**
 * PR-M003: JSON:API Request validation for VariantAttributeValue.
 */
class VariantAttributeValueRequest extends ResourceRequest
{
    public function rules(): array
    {
        $model = $this->model();

        return [
            'variantAttributeId' => [
                $this->isCreating() ? 'required' : 'sometimes',
                'integer',
                'exists:variant_attributes,id',
            ],
            'value' => [
                $this->isCreating() ? 'required' : 'sometimes',
                'string',
                'max:100',
            ],
            'code' => [
                $this->isCreating() ? 'required' : 'sometimes',
                'string',
                'max:50',
                Rule::unique('variant_attribute_values', 'code')
                    ->where('variant_attribute_id', $this->input('data.attributes.variantAttributeId'))
                    ->ignore($model),
            ],
            'displayValue' => ['nullable', 'string', 'max:100'],
            'colorHex' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sortOrder' => ['sometimes', 'integer', 'min:0'],
            'isActive' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'variantAttributeId.required' => 'El atributo padre es obligatorio.',
            'variantAttributeId.exists' => 'El atributo especificado no existe.',
            'value.required' => 'El valor es obligatorio.',
            'value.max' => 'El valor no puede exceder :max caracteres.',
            'code.required' => 'El codigo es obligatorio.',
            'code.unique' => 'Este codigo ya existe para este atributo.',
            'code.max' => 'El codigo no puede exceder :max caracteres.',
            'colorHex.regex' => 'El color debe ser un codigo hexadecimal valido (ej: #FF0000).',
            'sortOrder.min' => 'El orden debe ser un numero positivo.',
        ];
    }
}
