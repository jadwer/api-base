<?php

namespace Modules\Finance\JsonApi\V1\APInvoices;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class APInvoiceRequest extends ResourceRequest
{
    public function rules(): array
    {
        $apinvoice = $this->model();
        
        return [
            'contact_id' => ['required', 'string'],
            'invoice_number' => ['required', 'string', 'max:255'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['required', 'date'],
            'currency' => ['nullable', 'string', 'max:255'],
            'exchange_rate' => ['nullable', 'string'],
            'subtotal' => ['required', 'string'],
            'tax_total' => ['required', 'string'],
            'total' => ['required', 'string'],
            'status' => ['required', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'contact_id.required' => 'El campo Contact id es obligatorio.',
            'invoice_number.required' => 'El campo Invoice number es obligatorio.',
            'invoice_number.string' => 'El campo Invoice number debe ser texto.',
            'invoice_number.max' => 'El campo Invoice number no puede tener más de 255 caracteres.',
            'invoice_date.required' => 'El campo Invoice date es obligatorio.',
            'invoice_date.date' => 'El campo Invoice date debe ser una fecha válida.',
            'due_date.required' => 'El campo Due date es obligatorio.',
            'due_date.date' => 'El campo Due date debe ser una fecha válida.',
            'currency.string' => 'El campo Currency debe ser texto.',
            'currency.max' => 'El campo Currency no puede tener más de 255 caracteres.',
            'subtotal.required' => 'El campo Subtotal es obligatorio.',
            'tax_total.required' => 'El campo Tax total es obligatorio.',
            'total.required' => 'El campo Total es obligatorio.',
            'status.required' => 'El campo Status es obligatorio.',
            'status.string' => 'El campo Status debe ser texto.',
            'status.max' => 'El campo Status no puede tener más de 255 caracteres.',
        ];
    }
}
