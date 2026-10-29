<?php

namespace Modules\Ecommerce\JsonApi\V1\Coupons;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class CouponRequest extends ResourceRequest
{
    /**
     * Hook to modify existing data before merging with request data.
     *
     * This fixes a conflict where the JSON:API document's 'type' field (resource type)
     * gets confused with the model's 'type' attribute (couponType) during PATCH requests.
     *
     * @param object $model
     * @param array $existing
     * @return array
     */
    protected function withExisting(object $model, array $existing): array
    {
        // Ensure couponType uses the model's actual type value, not the JSON:API resource type
        if (isset($existing['attributes'])) {
            $existing['attributes']['couponType'] = $model->type;
        }

        return $existing;
    }

    public function rules(): array
    {
        $coupon = $this->model();
        $isUpdate = $this->method() === 'PATCH' || $coupon !== null;
        
        return [
            'code' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255', Rule::unique('coupons')->ignore($coupon?->id)],
            'name' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'couponType' => [$isUpdate ? 'sometimes' : 'required', 'string', Rule::in(['percentage', 'fixed_amount', 'free_shipping'])],
            'value' => [$isUpdate ? 'sometimes' : 'required', 'numeric', 'min:0'],
            'minAmount' => ['nullable', 'numeric', 'min:0'],
            'maxAmount' => ['nullable', 'numeric', 'min:0'],
            'maxUses' => ['nullable', 'integer', 'min:1'],
            // usedCount is readOnly in schema - not accepted via API
            'startsAt' => ['nullable', 'date'],
            'expiresAt' => ['nullable', 'date', 'after:startsAt'],
            'isActive' => [$isUpdate ? 'sometimes' : 'required', 'boolean'],
            'customerIds' => ['nullable', 'array'],
            'productIds' => ['nullable', 'array'],
            'categoryIds' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'El campo clave es obligatorio.',
            'code.string' => 'El campo clave debe ser texto.',
            'code.max' => 'El campo clave no puede tener más de 255 caracteres.',
            'code.unique' => 'Este código ya está en uso.',
            'name.required' => 'El campo nombre es obligatorio.',
            'name.string' => 'El campo nombre debe ser texto.',
            'name.max' => 'El campo nombre no puede tener más de 255 caracteres.',
            'description.string' => 'El campo descripción debe ser texto.',
            'couponType.required' => 'El campo tipo es obligatorio.',
            'couponType.string' => 'El campo tipo debe ser texto.',
            'couponType.in' => 'El campo tipo debe ser: percentage, fixed_amount o free_shipping.',
            'value.required' => 'El campo valor es obligatorio.',
            'value.numeric' => 'El campo valor debe ser numérico.',
            'value.min' => 'El campo valor debe ser mayor o igual a 0.',
            'minAmount.numeric' => 'El campo monto mínimo debe ser numérico.',
            'maxAmount.numeric' => 'El campo monto máximo debe ser numérico.',
            'maxUses.integer' => 'El campo usos máximos debe ser un número entero.',
            'usedCount.integer' => 'El campo usos debe ser un número entero.',
            'startsAt.date' => 'El campo fecha de inicio debe ser una fecha válida.',
            'expiresAt.date' => 'El campo fecha de vencimiento debe ser una fecha válida.',
            'expiresAt.after' => 'El campo fecha de vencimiento debe ser posterior a Starts at.',
            'isActive.required' => 'El campo activo es obligatorio.',
            'isActive.boolean' => 'El campo activo debe ser verdadero o falso.',
            'customerIds.array' => 'El campo clientes debe ser un arreglo.',
            'productIds.array' => 'El campo productos debe ser un arreglo.',
            'categoryIds.array' => 'El campo categorías debe ser un arreglo.',
        ];
    }
}
