<?php

namespace Modules\Finance\JsonApi\V1\APInvoices;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;
use Illuminate\Validation\Rule;

class APInvoiceRequest extends ResourceRequest
{
    public function rules(): array
    {
        $apinvoice = $this->model();
        
        return [
            'contactId' => ['required', 'integer'],
            'contact' => JsonApiRule::toOne(),
            'invoiceNumber' => ['required', 'string', 'max:255'],
            'invoiceDate' => ['required', 'date'],
            'dueDate' => ['required', 'date'],
            'currency' => ['nullable', 'string', 'max:255'],
            'exchangeRate' => ['nullable', 'numeric', 'min:0'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'taxTotal' => ['required', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'contactId.required' => 'El campo Contact id es obligatorio.',
            'invoiceNumber.required' => 'El campo Invoice number es obligatorio.',
            'invoiceNumber.string' => 'El campo Invoice number debe ser texto.',
            'invoiceNumber.max' => 'El campo Invoice number no puede tener más de 255 caracteres.',
            'invoiceDate.required' => 'El campo Invoice date es obligatorio.',
            'invoiceDate.date' => 'El campo Invoice date debe ser una fecha válida.',
            'dueDate.required' => 'El campo Due date es obligatorio.',
            'dueDate.date' => 'El campo Due date debe ser una fecha válida.',
            'currency.string' => 'El campo Currency debe ser texto.',
            'currency.max' => 'El campo Currency no puede tener más de 255 caracteres.',
            'subtotal.required' => 'El campo Subtotal es obligatorio.',
            'taxTotal.required' => 'El campo Tax total es obligatorio.',
            'total.required' => 'El campo Total es obligatorio.',
            'status.required' => 'El campo Status es obligatorio.',
            'status.string' => 'El campo Status debe ser texto.',
            'status.max' => 'El campo Status no puede tener más de 255 caracteres.',
        ];
    }
}
