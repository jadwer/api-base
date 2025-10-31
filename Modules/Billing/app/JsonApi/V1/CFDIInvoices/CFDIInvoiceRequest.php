<?php

namespace Modules\Billing\JsonApi\V1\CFDIInvoices;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class CFDIInvoiceRequest extends ResourceRequest
{
    public function rules(): array
    {
        return [
            'companySettingId' => [
                'required',
                'integer',
                'exists:company_settings,id',
            ],
            'contactId' => [
                'required',
                'integer',
                'exists:contacts,id',
            ],
            'arInvoiceId' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:ar_invoices,id',
            ],
            'series' => [
                'required',
                'string',
                'max:10',
            ],
            'folio' => [
                'required',
                'integer',
                'min:1',
            ],
            'tipoComprobante' => [
                'sometimes',
                'string',
                'in:I,E,T,N,P',
            ],
            'receptorRfc' => [
                'required',
                'string',
                'size:13',
                'regex:/^[A-Z]{4}[0-9]{6}[A-Z0-9]{3}$/',
            ],
            'receptorNombre' => [
                'required',
                'string',
                'max:255',
            ],
            'receptorUsoCfdi' => [
                'sometimes',
                'string',
                'max:10',
            ],
            'receptorRegimenFiscal' => [
                'sometimes',
                'nullable',
                'string',
                'max:10',
            ],
            'receptorDomicilioFiscal' => [
                'sometimes',
                'nullable',
                'string',
                'size:5',
                'regex:/^[0-9]{5}$/',
            ],
            'subtotal' => [
                'required',
                'integer',
                'min:1',
            ],
            'total' => [
                'required',
                'integer',
                'min:1',
            ],
            'descuento' => [
                'sometimes',
                'integer',
                'min:0',
            ],
            'iva' => [
                'sometimes',
                'integer',
                'min:0',
            ],
            'ieps' => [
                'sometimes',
                'integer',
                'min:0',
            ],
            'isrRetenido' => [
                'sometimes',
                'integer',
                'min:0',
            ],
            'ivaRetenido' => [
                'sometimes',
                'integer',
                'min:0',
            ],
            'moneda' => [
                'sometimes',
                'string',
                'size:3',
            ],
            'tipoCambio' => [
                'sometimes',
                'numeric',
                'min:0',
            ],
            'formaPago' => [
                'sometimes',
                'nullable',
                'string',
                'max:2',
            ],
            'metodoPago' => [
                'sometimes',
                'string',
                'in:PUE,PPD',
            ],
            'condicionesPago' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'cfdiRelacionadoTipo' => [
                'sometimes',
                'nullable',
                'string',
                'max:10',
            ],
            'cfdiRelacionadoUuids' => [
                'sometimes',
                'nullable',
                'array',
            ],
            'status' => [
                'sometimes',
                'string',
                'in:draft,valid,cancelled,error',
            ],
            'fechaEmision' => [
                'required',
                'date',
            ],
            'metadata' => [
                'sometimes',
                'nullable',
                'array',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'companySettingId.required' => 'La configuración de empresa es obligatoria.',
            'companySettingId.exists' => 'La configuración de empresa no existe.',
            'contactId.required' => 'El contacto es obligatorio.',
            'contactId.exists' => 'El contacto no existe.',
            'series.required' => 'La serie es obligatoria.',
            'folio.required' => 'El folio es obligatorio.',
            'folio.min' => 'El folio debe ser mayor a 0.',
            'tipoComprobante.in' => 'El tipo de comprobante debe ser I, E, T, N o P.',
            'receptorRfc.required' => 'El RFC del receptor es obligatorio.',
            'receptorRfc.size' => 'El RFC debe tener exactamente 13 caracteres.',
            'receptorRfc.regex' => 'El formato del RFC es inválido.',
            'receptorNombre.required' => 'El nombre del receptor es obligatorio.',
            'receptorDomicilioFiscal.regex' => 'El código postal debe tener 5 dígitos.',
            'subtotal.required' => 'El subtotal es obligatorio.',
            'subtotal.min' => 'El subtotal debe ser mayor a 0.',
            'total.required' => 'El total es obligatorio.',
            'total.min' => 'El total debe ser mayor a 0.',
            'moneda.size' => 'La moneda debe ser un código ISO de 3 caracteres.',
            'metodoPago.in' => 'El método de pago debe ser PUE o PPD.',
            'fechaEmision.required' => 'La fecha de emisión es obligatoria.',
            'fechaEmision.date' => 'La fecha de emisión debe ser una fecha válida.',
        ];
    }
}
