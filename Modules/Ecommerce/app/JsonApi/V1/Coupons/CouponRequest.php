<?php

namespace Modules\Ecommerce\JsonApi\V1\Coupons;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class CouponRequest extends ResourceRequest
{
    public function rules(): array
    {
        $coupon = $this->model();
        $isUpdate = $this->method() === 'PATCH' || $coupon !== null;
        
        return [
            'code' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255', Rule::unique('coupons')->ignore($coupon?->id)],
            'name' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'couponType' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'value' => [$isUpdate ? 'sometimes' : 'required', 'numeric', 'min:0'],
            'minAmount' => ['nullable', 'numeric', 'min:0'],
            'maxAmount' => ['nullable', 'numeric', 'min:0'],
            'maxUses' => ['nullable', 'integer', 'min:1'],
            'usedCount' => ['nullable', 'integer', 'min:0'],
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
            'code.required' => 'El campo Code es obligatorio.',
            'code.string' => 'El campo Code debe ser texto.',
            'code.max' => 'El campo Code no puede tener más de 255 caracteres.',
            'code.unique' => 'Este Code ya está en uso.',
            'name.required' => 'El campo Name es obligatorio.',
            'name.string' => 'El campo Name debe ser texto.',
            'name.max' => 'El campo Name no puede tener más de 255 caracteres.',
            'description.string' => 'El campo Description debe ser texto.',
            'couponType.required' => 'El campo Type es obligatorio.',
            'couponType.string' => 'El campo Type debe ser texto.',
            'couponType.max' => 'El campo Type no puede tener más de 255 caracteres.',
            'value.required' => 'El campo Value es obligatorio.',
            'value.numeric' => 'El campo Value debe ser numérico.',
            'value.min' => 'El campo Value debe ser mayor o igual a 0.',
            'minAmount.numeric' => 'El campo Min amount debe ser numérico.',
            'maxAmount.numeric' => 'El campo Max amount debe ser numérico.',
            'maxUses.integer' => 'El campo Max uses debe ser un número entero.',
            'usedCount.integer' => 'El campo Used count debe ser un número entero.',
            'startsAt.date' => 'El campo Starts at debe ser una fecha válida.',
            'expiresAt.date' => 'El campo Expires at debe ser una fecha válida.',
            'expiresAt.after' => 'El campo Expires at debe ser posterior a Starts at.',
            'isActive.required' => 'El campo Is active es obligatorio.',
            'isActive.boolean' => 'El campo Is active debe ser verdadero o falso.',
            'customerIds.array' => 'El campo Customer ids debe ser un arreglo.',
            'productIds.array' => 'El campo Product ids debe ser un arreglo.',
            'categoryIds.array' => 'El campo Category ids debe ser un arreglo.',
        ];
    }
}
