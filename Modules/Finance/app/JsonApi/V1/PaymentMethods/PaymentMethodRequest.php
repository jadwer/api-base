<?php

namespace Modules\Finance\JsonApi\V1\PaymentMethods;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class PaymentMethodRequest extends ResourceRequest
{
    public function rules(): array
    {
        $paymentmethod = $this->model();
        
        return [
            'code' => ['nullable', 'string', 'max:255', Rule::unique('payment_methods')->ignore($paymentmethod?->id)],
            'name' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:255'],
            'requires_reference' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.string' => 'El campo Code debe ser texto.',
            'code.max' => 'El campo Code no puede tener más de 255 caracteres.',
            'code.unique' => 'Este Code ya está en uso.',
            'name.string' => 'El campo Name debe ser texto.',
            'name.max' => 'El campo Name no puede tener más de 255 caracteres.',
            'type.string' => 'El campo Type debe ser texto.',
            'type.max' => 'El campo Type no puede tener más de 255 caracteres.',
            'requires_reference.boolean' => 'El campo Requires reference debe ser verdadero o falso.',
            'is_active.boolean' => 'El campo Is active debe ser verdadero o falso.',
        ];
    }
}
