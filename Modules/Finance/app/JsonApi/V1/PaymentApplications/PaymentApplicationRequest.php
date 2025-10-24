<?php

namespace Modules\Finance\JsonApi\V1\PaymentApplications;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class PaymentApplicationRequest extends ResourceRequest
{
    public function rules(): array
    {
        $paymentapplication = $this->model();
        
        return [
            'payment_id' => ['nullable', 'integer'],
            'ar_invoice_id' => ['nullable', 'integer'],
            'amount' => ['nullable', 'string'],
            'application_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_id.integer' => 'El campo Payment id debe ser un número entero.',
            'ar_invoice_id.integer' => 'El campo Ar invoice id debe ser un número entero.',
            'application_date.date' => 'El campo Application date debe ser una fecha válida.',
            'notes.string' => 'El campo Notes debe ser texto.',
            'is_active.boolean' => 'El campo Is active debe ser verdadero o falso.',
        ];
    }
}
