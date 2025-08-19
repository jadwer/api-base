<?php

namespace Modules\Finance\JsonApi\V1\APPayments;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class APPaymentRequest extends ResourceRequest
{
    public function rules(): array
    {
        $appayment = $this->model();
        
        return [
            'contact_id' => ['required', 'string'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'string'],
            'bank_account_id' => ['required', 'string'],
            'status' => ['required', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'contact_id.required' => 'El campo Contact id es obligatorio.',
            'payment_date.required' => 'El campo Payment date es obligatorio.',
            'payment_date.date' => 'El campo Payment date debe ser una fecha válida.',
            'payment_method.required' => 'El campo Payment method es obligatorio.',
            'payment_method.string' => 'El campo Payment method debe ser texto.',
            'payment_method.max' => 'El campo Payment method no puede tener más de 255 caracteres.',
            'currency.string' => 'El campo Currency debe ser texto.',
            'currency.max' => 'El campo Currency no puede tener más de 255 caracteres.',
            'amount.required' => 'El campo Amount es obligatorio.',
            'bank_account_id.required' => 'El campo Bank account id es obligatorio.',
            'status.required' => 'El campo Status es obligatorio.',
            'status.string' => 'El campo Status debe ser texto.',
            'status.max' => 'El campo Status no puede tener más de 255 caracteres.',
        ];
    }
}
