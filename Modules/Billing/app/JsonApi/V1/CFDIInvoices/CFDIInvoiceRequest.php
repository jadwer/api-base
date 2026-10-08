<?php

namespace Modules\Billing\JsonApi\V1\CFDIInvoices;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Modules\Contacts\Support\SatCatalogs;

class CFDIInvoiceRequest extends ResourceRequest
{
    public function rules(): array
    {
        $creating = $this->isMethod('POST');

        return [
            'companySettingId' => [
                $creating ? 'required' : 'sometimes',
                'integer',
                'exists:company_settings,id',
            ],
            'contactId' => [
                $creating ? 'required' : 'sometimes',
                'integer',
                'exists:contacts,id',
            ],
            'arInvoiceId' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:ar_invoices,id',
            ],
            'branchId' => ['sometimes', 'nullable', 'integer', 'exists:branches,id'],
            'series' => [
                $creating ? 'required' : 'sometimes',
                'string',
                'max:10',
            ],
            'folio' => [
                $creating ? 'required' : 'sometimes',
                'integer',
                'min:1',
            ],
            'tipoComprobante' => [
                'sometimes',
                'string',
                'in:I,E,T,N,P',
            ],
            'receptorRfc' => [
                $creating ? 'required' : 'sometimes',
                'string',
                'between:12,13',
                'regex:/^([A-Z&Ñ]{3}[0-9]{6}[A-Z0-9]{3}|[A-Z&Ñ]{4}[0-9]{6}[A-Z0-9]{3})$/',
            ],
            'receptorNombre' => [
                $creating ? 'required' : 'sometimes',
                'string',
                'max:255',
            ],
            // Mismos catalogos que sirve contact-catalogs (SatCatalogs, regla 7)
            'receptorUsoCfdi' => [
                'sometimes',
                'nullable',
                'string',
                Rule::in(SatCatalogs::usoCfdiCodes()),
            ],
            'receptorRegimenFiscal' => [
                'sometimes',
                'nullable',
                'string',
                Rule::in(SatCatalogs::regimenFiscalCodes()),
            ],
            'receptorDomicilioFiscal' => [
                'sometimes',
                'nullable',
                'string',
                'size:5',
                'regex:/^[0-9]{5}$/',
            ],
            'subtotal' => [
                $creating ? 'required' : 'sometimes',
                'integer',
                'min:1',
            ],
            'total' => [
                $creating ? 'required' : 'sometimes',
                'integer',
                'min:1',
            ],
            'descuento' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
            ],
            'iva' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
            ],
            'ieps' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
            ],
            'isrRetenido' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
            ],
            'ivaRetenido' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
            ],
            'moneda' => [
                'sometimes',
                'nullable',
                'string',
                'size:3',
            ],
            'tipoCambio' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
            ],
            // Misma tabla que sirve GET /sat/forma-pago
            'formaPago' => [
                'sometimes',
                'nullable',
                'string',
                Rule::exists('sat_forma_pago', 'clave'),
            ],
            'metodoPago' => [
                'sometimes',
                'nullable',
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
            // Refactor ciclo (Patron 1): status solo se valida en creacion (draft/error).
            // En update se acepta pero el Schema lo marca readOnlyOnUpdate -> se IGNORA
            // (no falla para no romper el form de edicion del FE, que envia status).
            // Pasar a 'valid'/'cancelled' es exclusivo de CFDIStampingService::stamp()/
            // cancel() (que llaman al PAC). Un PATCH no puede fingir una factura timbrada.
            'status' => $creating
                ? ['sometimes', 'string', 'in:draft,error']
                : ['sometimes', 'string'],
            'fechaEmision' => [
                $creating ? 'required' : 'sometimes',
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
            'receptorUsoCfdi.in' => 'El uso de CFDI no esta en el catalogo SAT.',
            'receptorRegimenFiscal.in' => 'El regimen fiscal no esta en el catalogo SAT.',
            'formaPago.exists' => 'La forma de pago no esta en el catalogo SAT.',
            'companySettingId.required' => 'La configuración de empresa es obligatoria.',
            'companySettingId.exists' => 'La configuración de empresa no existe.',
            'contactId.required' => 'El contacto es obligatorio.',
            'contactId.exists' => 'El contacto no existe.',
            'series.required' => 'La serie es obligatoria.',
            'folio.required' => 'El folio es obligatorio.',
            'folio.min' => 'El folio debe ser mayor a 0.',
            'tipoComprobante.in' => 'El tipo de comprobante debe ser I, E, T, N o P.',
            'receptorRfc.required' => 'El RFC del receptor es obligatorio.',
            'receptorRfc.between' => 'El RFC debe tener 12 caracteres (persona moral) o 13 (persona física).',
            'receptorRfc.regex' => 'El formato del RFC es inválido. Debe ser 3-4 letras + 6 dígitos + 3 alfanuméricos.',
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
