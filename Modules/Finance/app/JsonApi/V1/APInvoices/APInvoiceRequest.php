<?php

namespace Modules\Finance\JsonApi\V1\APInvoices;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;
use Modules\Contacts\Models\Contact;

class APInvoiceRequest extends ResourceRequest
{
    public function rules(): array
    {
        $apinvoice = $this->model();

        return [
            'invoiceNumber' => ['required', 'string', 'max:255', Rule::unique('ap_invoices', 'invoice_number')->ignore($apinvoice?->id)],
            'invoiceDate' => ['required', 'date'],
            'dueDate' => ['required', 'date'],
            'contactId' => [
                'required',
                'integer',
                'exists:contacts,id',
                function ($attribute, $value, $fail) {
                    $contact = Contact::find($value);
                    if (!$contact || !$contact->is_supplier) {
                        $fail('El contacto debe ser un proveedor válido (is_supplier = true).');
                    }
                }
            ],
            'purchaseOrderId' => ['nullable', 'integer'],
            'currency' => ['nullable', 'string', 'max:255'],
            'subtotal' => ['required', 'numeric'],
            'taxAmount' => ['required', 'numeric'],
            'totalAmount' => ['required', 'numeric'],
            'paidAmount' => ['nullable', 'numeric'],
            'status' => ['nullable', 'string', 'max:255'],
            'journalEntryId' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
            'isActive' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'invoiceNumber.string' => 'El campo Invoice number debe ser texto.',
            'invoiceNumber.max' => 'El campo Invoice number no puede tener más de 255 caracteres.',
            'invoiceNumber.unique' => 'Este Invoice number ya está en uso.',
            'invoiceDate.date' => 'El campo Invoice date debe ser una fecha válida.',
            'dueDate.date' => 'El campo Due date debe ser una fecha válida.',
            'contactId.integer' => 'El campo Contact id debe ser un número entero.',
            'purchaseOrderId.integer' => 'El campo Purchase order id debe ser un número entero.',
            'currency.string' => 'El campo Currency debe ser texto.',
            'currency.max' => 'El campo Currency no puede tener más de 255 caracteres.',
            'subtotal.numeric' => 'El campo Subtotal debe ser un número.',
            'taxAmount.numeric' => 'El campo Tax amount debe ser un número.',
            'totalAmount.numeric' => 'El campo Total amount debe ser un número.',
            'paidAmount.numeric' => 'El campo Paid amount debe ser un número.',
            'status.string' => 'El campo Status debe ser texto.',
            'status.max' => 'El campo Status no puede tener más de 255 caracteres.',
            'journalEntryId.integer' => 'El campo Journal entry id debe ser un número entero.',
            'notes.string' => 'El campo Notes debe ser texto.',
            'metadata.array' => 'El campo Metadata debe ser un arreglo.',
            'isActive.boolean' => 'El campo Is active debe ser verdadero o falso.',
        ];
    }
}
