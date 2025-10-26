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
            'paymentId' => ['nullable', 'integer'],
            'arInvoiceId' => ['nullable', 'integer'],
            'amount' => ['nullable', 'numeric'],
            'applicationDate' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'isActive' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'paymentId.integer' => 'El campo Payment id debe ser un número entero.',
            'arInvoiceId.integer' => 'El campo Ar invoice id debe ser un número entero.',
            'amount.numeric' => 'El campo Amount debe ser un número.',
            'applicationDate.date' => 'El campo Application date debe ser una fecha válida.',
            'notes.string' => 'El campo Notes debe ser texto.',
            'isActive.boolean' => 'El campo Is active debe ser verdadero o falso.',
        ];
    }
}
