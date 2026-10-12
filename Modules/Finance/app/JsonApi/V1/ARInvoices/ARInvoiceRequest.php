<?php

namespace Modules\Finance\JsonApi\V1\ARInvoices;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Modules\Finance\JsonApi\Concerns\ValidatesFinancialImmutability;
use Illuminate\Validation\Rule;
use Modules\Contacts\Models\Contact;

class ARInvoiceRequest extends ResourceRequest
{
    use ValidatesFinancialImmutability;

    protected function immutableStatuses(): array
    {
        return ['posted', 'partial', 'paid', 'void', 'voided', 'cancelled'];
    }
    public function rules(): array
    {
        $arinvoice = $this->model();

        return [
            'invoiceNumber' => ['required', 'string', 'max:255', Rule::unique('ar_invoices', 'invoice_number')->ignore($arinvoice?->id)],
            'invoiceDate' => ['required', 'date'],
            'dueDate' => ['required', 'date'],
            'contactId' => [
                'required',
                'integer',
                'exists:contacts,id',
                function ($attribute, $value, $fail) {
                    $contact = Contact::find($value);
                    if (!$contact || !$contact->is_customer) {
                        $fail('El contacto debe ser un cliente válido (is_customer = true).');
                    }
                }
            ],
            'salesOrderId' => ['nullable', 'integer'],
            'currency' => ['nullable', 'string', 'max:255'],
            'subtotal' => ['required', 'numeric'],
            'taxAmount' => ['required', 'numeric'],
            'totalAmount' => ['required', 'numeric'],
            'paidAmount' => ['nullable', 'numeric'],
            'paidDate' => ['nullable', 'date'],
            'status' => ['nullable', 'string', \Illuminate\Validation\Rule::in(['draft', 'pending', 'posted', 'partial', 'paid', 'void', 'voided', 'cancelled'])],
            'journalEntryId' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
            'isActive' => ['nullable', 'boolean'],
            // FI-M002: Early payment discount fields
            'discountPercent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discountDays' => ['nullable', 'integer', 'min:1', 'max:365'],
            'discountDate' => ['nullable', 'date'],
            'discountAmount' => ['nullable', 'numeric', 'min:0'],
            'discountApplied' => ['nullable', 'boolean'],
            'discountAppliedAmount' => ['nullable', 'numeric', 'min:0'],
            'discountAppliedDate' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'invoiceNumber.string' => 'El campo número de factura debe ser texto.',
            'invoiceNumber.max' => 'El campo número de factura no puede tener más de 255 caracteres.',
            'invoiceNumber.unique' => 'Este Invoice number ya está en uso.',
            'invoiceDate.date' => 'El campo fecha de factura debe ser una fecha válida.',
            'dueDate.date' => 'El campo fecha de vencimiento debe ser una fecha válida.',
            'contactId.integer' => 'El campo contacto debe ser un número entero.',
            'salesOrderId.integer' => 'El campo pedido debe ser un número entero.',
            'currency.string' => 'El campo moneda debe ser texto.',
            'currency.max' => 'El campo moneda no puede tener más de 255 caracteres.',
            'subtotal.numeric' => 'El campo subtotal debe ser un número.',
            'taxAmount.numeric' => 'El campo impuestos debe ser un número.',
            'totalAmount.numeric' => 'El campo total debe ser un número.',
            'paidAmount.numeric' => 'El campo importe pagado debe ser un número.',
            'status.string' => 'El campo estado debe ser texto.',
            'status.max' => 'El campo estado no puede tener más de 255 caracteres.',
            'journalEntryId.integer' => 'El campo póliza debe ser un número entero.',
            'notes.string' => 'El campo notas debe ser texto.',
            'metadata.array' => 'El campo metadatos debe ser un arreglo.',
            'isActive.boolean' => 'El campo activo debe ser verdadero o falso.',
            // FI-M002: Early payment discount messages
            'discountPercent.numeric' => 'El porcentaje de descuento debe ser un número.',
            'discountPercent.min' => 'El porcentaje de descuento no puede ser negativo.',
            'discountPercent.max' => 'El porcentaje de descuento no puede exceder 100%.',
            'discountDays.integer' => 'Los días de descuento deben ser un número entero.',
            'discountDays.min' => 'Los días de descuento deben ser al menos 1.',
            'discountDays.max' => 'Los días de descuento no pueden exceder 365.',
            'discountDate.date' => 'La fecha límite de descuento debe ser una fecha válida.',
            'discountAmount.numeric' => 'El monto del descuento debe ser un número.',
            'discountAmount.min' => 'El monto del descuento no puede ser negativo.',
        ];
    }
}
