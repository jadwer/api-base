<?php

namespace Modules\Finance\JsonApi\V1\ARInvoices;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class ARInvoiceRequest extends ResourceRequest
{
    public function rules(): array
    {
        $arinvoice = $this->model();
        
        return [
            'invoice_number' => ['nullable', 'string', 'max:255', Rule::unique('ar_invoices')->ignore($arinvoice?->id)],
            'invoice_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'customer_id' => ['nullable', 'integer'],
            'currency' => ['nullable', 'string', 'max:255'],
            'subtotal' => ['nullable', 'string'],
            'tax_amount' => ['nullable', 'string'],
            'total_amount' => ['nullable', 'string'],
            'paid_amount' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:255'],
            'journal_entry_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'invoice_number.string' => 'El campo Invoice number debe ser texto.',
            'invoice_number.max' => 'El campo Invoice number no puede tener más de 255 caracteres.',
            'invoice_number.unique' => 'Este Invoice number ya está en uso.',
            'invoice_date.date' => 'El campo Invoice date debe ser una fecha válida.',
            'due_date.date' => 'El campo Due date debe ser una fecha válida.',
            'customer_id.integer' => 'El campo Customer id debe ser un número entero.',
            'currency.string' => 'El campo Currency debe ser texto.',
            'currency.max' => 'El campo Currency no puede tener más de 255 caracteres.',
            'status.string' => 'El campo Status debe ser texto.',
            'status.max' => 'El campo Status no puede tener más de 255 caracteres.',
            'journal_entry_id.integer' => 'El campo Journal entry id debe ser un número entero.',
            'notes.string' => 'El campo Notes debe ser texto.',
            'metadata.array' => 'El campo Metadata debe ser un arreglo.',
            'is_active.boolean' => 'El campo Is active debe ser verdadero o falso.',
        ];
    }
}
