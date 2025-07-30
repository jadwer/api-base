<?php

namespace Modules\Ecommerce\JsonApi\V1\Coupons;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class CouponRequest extends ResourceRequest
{
    public function rules(): array
    {
        $coupon = $this->model();
        
        return [
            'code' => ['required', 'string', 'max:255', Rule::unique('coupons')->ignore($coupon?->id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', 'max:255'],
            'value' => ['required', 'string'],
            'min_amount' => ['nullable', 'string'],
            'max_amount' => ['nullable', 'string'],
            'max_uses' => ['nullable', 'integer'],
            'used_count' => ['required', 'integer'],
            'starts_at' => ['nullable', 'string'],
            'expires_at' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
            'customer_ids' => ['nullable', 'array'],
            'product_ids' => ['nullable', 'array'],
            'category_ids' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
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
            'type.required' => 'El campo Type es obligatorio.',
            'type.string' => 'El campo Type debe ser texto.',
            'type.max' => 'El campo Type no puede tener más de 255 caracteres.',
            'value.required' => 'El campo Value es obligatorio.',
            'max_uses.integer' => 'El campo Max uses debe ser un número entero.',
            'used_count.required' => 'El campo Used count es obligatorio.',
            'used_count.integer' => 'El campo Used count debe ser un número entero.',
            'is_active.required' => 'El campo Is active es obligatorio.',
            'is_active.boolean' => 'El campo Is active debe ser verdadero o falso.',
            'customer_ids.array' => 'El campo Customer ids debe ser un arreglo.',
            'product_ids.array' => 'El campo Product ids debe ser un arreglo.',
            'category_ids.array' => 'El campo Category ids debe ser un arreglo.',
        ];
    }
}
