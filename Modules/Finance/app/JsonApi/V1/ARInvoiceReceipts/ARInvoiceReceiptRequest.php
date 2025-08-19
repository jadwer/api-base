<?php

namespace Modules\Finance\JsonApi\V1\ARInvoiceReceipts;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class ARInvoiceReceiptRequest extends ResourceRequest
{
    public function rules(): array
    {
        $arinvoicereceipt = $this->model();
        
        return [
            'ar_invoice_id' => ['required', 'string'],
            'ar_receipt_id' => ['required', 'string'],
            'amount_applied' => ['required', 'string'],
            'applied_at' => ['required', 'date'],
            'exchange_rate_at_apply' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'ar_invoice_id.required' => 'El campo Ar invoice id es obligatorio.',
            'ar_receipt_id.required' => 'El campo Ar receipt id es obligatorio.',
            'amount_applied.required' => 'El campo Amount applied es obligatorio.',
            'applied_at.required' => 'El campo Applied at es obligatorio.',
            'applied_at.date' => 'El campo Applied at debe ser una fecha válida.',
        ];
    }
}
