<?php

namespace Modules\Sales\JsonApi\V1\DiscountRules;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;
use Illuminate\Validation\Rule;
use Modules\Sales\Models\DiscountRule;

class DiscountRuleRequest extends ResourceRequest
{
    public function rules(): array
    {
        $discountRule = $this->model();
        $isUpdate = $this->isMethod('PATCH');

        $rules = [
            'name' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:255',
            ],
            'code' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:50',
                Rule::unique('discount_rules', 'code')->ignore($discountRule?->id),
            ],
            'description' => ['nullable', 'string'],
            'discountType' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                Rule::in([
                    DiscountRule::TYPE_PERCENTAGE,
                    DiscountRule::TYPE_FIXED_AMOUNT,
                    DiscountRule::TYPE_BUY_X_GET_Y,
                ]),
            ],
            'discountValue' => [
                $isUpdate ? 'sometimes' : 'required',
                'numeric',
                'min:0',
            ],
            'buyQuantity' => [
                'nullable',
                'integer',
                'min:1',
                'required_if:discountType,' . DiscountRule::TYPE_BUY_X_GET_Y,
            ],
            'getQuantity' => [
                'nullable',
                'integer',
                'min:1',
                'required_if:discountType,' . DiscountRule::TYPE_BUY_X_GET_Y,
            ],
            'appliesTo' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                Rule::in([
                    DiscountRule::APPLIES_TO_ORDER,
                    DiscountRule::APPLIES_TO_PRODUCT,
                    DiscountRule::APPLIES_TO_CATEGORY,
                    DiscountRule::APPLIES_TO_CUSTOMER,
                ]),
            ],
            'minOrderAmount' => ['nullable', 'numeric', 'min:0'],
            'minQuantity' => ['nullable', 'integer', 'min:1'],
            'maxDiscountAmount' => ['nullable', 'numeric', 'min:0'],
            'productIds' => ['nullable', 'array'],
            'productIds.*' => ['integer', 'exists:products,id'],
            'categoryIds' => ['nullable', 'array'],
            'categoryIds.*' => ['integer', 'exists:categories,id'],
            'customerIds' => ['nullable', 'array'],
            'customerIds.*' => ['integer', 'exists:contacts,id'],
            'customerClassifications' => ['nullable', 'array'],
            'customerClassifications.*' => ['string'],
            'startDate' => ['nullable', 'date'],
            'endDate' => ['nullable', 'date', 'after_or_equal:startDate'],
            'usageLimit' => ['nullable', 'integer', 'min:1'],
            'usagePerCustomer' => ['nullable', 'integer', 'min:1'],
            'priority' => ['nullable', 'integer', 'min:0'],
            'isCombinable' => ['nullable', 'boolean'],
            'isActive' => ['nullable', 'boolean'],
        ];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del descuento es obligatorio.',
            'name.max' => 'El nombre no puede exceder 255 caracteres.',
            'code.required' => 'El código del descuento es obligatorio.',
            'code.unique' => 'Este código de descuento ya existe.',
            'discountType.required' => 'El tipo de descuento es obligatorio.',
            'discountType.in' => 'El tipo de descuento no es válido.',
            'discountValue.required' => 'El valor del descuento es obligatorio.',
            'discountValue.min' => 'El valor del descuento debe ser mayor o igual a cero.',
            'buyQuantity.required_if' => 'La cantidad a comprar es obligatoria para descuentos "Compra X Lleva Y".',
            'getQuantity.required_if' => 'La cantidad gratis es obligatoria para descuentos "Compra X Lleva Y".',
            'appliesTo.required' => 'El campo "aplica a" es obligatorio.',
            'appliesTo.in' => 'El valor de "aplica a" no es válido.',
            'endDate.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'productIds.*.exists' => 'Uno o más productos no existen.',
            'categoryIds.*.exists' => 'Una o más categorías no existen.',
            'customerIds.*.exists' => 'Uno o más clientes no existen.',
        ];
    }
}
