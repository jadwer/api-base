<?php

namespace Modules\Finance\JsonApi\V1\APInvoices;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Modules\Finance\JsonApi\Concerns\ValidatesFinancialImmutability;
use Illuminate\Validation\Rule;
use Modules\Contacts\Models\Contact;

class APInvoiceRequest extends ResourceRequest
{
    use ValidatesFinancialImmutability;

    protected function immutableStatuses(): array
    {
        return ['posted', 'partial', 'paid', 'overdue', 'void', 'voided', 'cancelled'];
    }
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
            // Alta directa solo en borrador: una factura "posted" creada por aqui
            // no tiene poliza y la cartera la sumaba (auditoria 2026-10, punto 14).
            // La contabilizada nace en el Service con su poliza. En update el
            // estado no cambia por PATCH (ValidatesFinancialImmutability).
            'status' => $apinvoice?->exists
                ? ['nullable', 'string', Rule::in(['draft', 'pending', 'posted', 'partial', 'paid', 'overdue', 'void', 'voided', 'cancelled'])]
                : ['nullable', 'string', Rule::in(['draft'])],
            'journalEntryId' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
            'isActive' => ['nullable', 'boolean'],
            // Conciliacion contra la OC (enum de la tabla ap_invoices)
            'reconciliationStatus' => ['sometimes', 'string', Rule::in(['pending', 'matched', 'discrepancy', 'approved'])],
            'reconciledAt' => ['nullable', 'date'],
            'reconciliationNotes' => ['nullable', 'string'],
            'discrepancies' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'invoiceNumber.string' => 'El campo número de factura debe ser texto.',
            'invoiceNumber.max' => 'El campo número de factura no puede tener más de 255 caracteres.',
            'invoiceNumber.unique' => 'Este número de factura ya está en uso.',
            'invoiceDate.date' => 'El campo fecha de factura debe ser una fecha válida.',
            'dueDate.date' => 'El campo fecha de vencimiento debe ser una fecha válida.',
            'contactId.integer' => 'El campo contacto debe ser un número entero.',
            'purchaseOrderId.integer' => 'El campo orden de compra debe ser un número entero.',
            'currency.string' => 'El campo moneda debe ser texto.',
            'currency.max' => 'El campo moneda no puede tener más de 255 caracteres.',
            'subtotal.numeric' => 'El campo subtotal debe ser un número.',
            'taxAmount.numeric' => 'El campo impuestos debe ser un número.',
            'totalAmount.numeric' => 'El campo total debe ser un número.',
            'paidAmount.numeric' => 'El campo importe pagado debe ser un número.',
            'status.string' => 'El campo estado debe ser texto.',
            'status.in' => 'La factura se crea en borrador; la contabiliza el sistema con su póliza.',
            'status.max' => 'El campo estado no puede tener más de 255 caracteres.',
            'journalEntryId.integer' => 'El campo póliza debe ser un número entero.',
            'notes.string' => 'El campo notas debe ser texto.',
            'metadata.array' => 'El campo metadatos debe ser un arreglo.',
            'isActive.boolean' => 'El campo activo debe ser verdadero o falso.',
        ];
    }
}
