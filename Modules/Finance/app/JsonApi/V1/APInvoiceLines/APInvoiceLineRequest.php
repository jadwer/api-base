<?php

namespace Modules\Finance\JsonApi\V1\APInvoiceLines;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class APInvoiceLineRequest extends ResourceRequest
{
    public function rules(): array
    {
        $apinvoiceline = $this->model();
        
        return [
            'ap_invoice_id' => ['required', 'string'],
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'string'],
            'unit_price' => ['required', 'string'],
            'discount' => ['required', 'string'],
            'line_total' => ['required', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'ap_invoice_id.required' => 'El campo Ap invoice id es obligatorio.',
            'description.required' => 'El campo Description es obligatorio.',
            'description.string' => 'El campo Description debe ser texto.',
            'description.max' => 'El campo Description no puede tener más de 255 caracteres.',
            'quantity.required' => 'El campo Quantity es obligatorio.',
            'unit_price.required' => 'El campo Unit price es obligatorio.',
            'discount.required' => 'El campo Discount es obligatorio.',
            'line_total.required' => 'El campo Line total es obligatorio.',
        ];
    }
}
