<?php

namespace Modules\Finance\JsonApi\V1\APInvoicePayments;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class APInvoicePaymentRequest extends ResourceRequest
{
    public function rules(): array
    {
        $apinvoicepayment = $this->model();
        
        return [
            'ap_invoice_id' => ['required', 'string'],
            'ap_payment_id' => ['required', 'string'],
            'amount_applied' => ['required', 'string'],
            'applied_at' => ['required', 'date'],
            'exchange_rate_at_apply' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'ap_invoice_id.required' => 'El campo Ap invoice id es obligatorio.',
            'ap_payment_id.required' => 'El campo Ap payment id es obligatorio.',
            'amount_applied.required' => 'El campo Amount applied es obligatorio.',
            'applied_at.required' => 'El campo Applied at es obligatorio.',
            'applied_at.date' => 'El campo Applied at debe ser una fecha válida.',
        ];
    }
}
