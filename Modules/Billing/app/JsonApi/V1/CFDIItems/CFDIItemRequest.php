<?php

namespace Modules\Billing\JsonApi\V1\CFDIItems;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class CFDIItemRequest extends ResourceRequest
{
    public function rules(): array
    {
        $creating = $this->isMethod('POST');

        return [
            'cfdiInvoiceId' => [
                $creating ? 'required' : 'sometimes',
                'integer',
                'exists:cfdi_invoices,id',
            ],
            'productId' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:products,id',
            ],
            'numeroLinea' => [
                $creating ? 'required' : 'sometimes',
                'integer',
                'min:1',
            ],
            'claveProdServ' => [
                $creating ? 'required' : 'sometimes',
                'string',
                'max:20',
            ],
            'claveUnidad' => [
                'sometimes',
                'string',
                'max:10',
            ],
            'unidad' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
            ],
            'cantidad' => [
                $creating ? 'required' : 'sometimes',
                'numeric',
                'min:0.000001',
            ],
            'descripcion' => [
                $creating ? 'required' : 'sometimes',
                'string',
            ],
            'noIdentificacion' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],
            'valorUnitario' => [
                $creating ? 'required' : 'sometimes',
                'integer',
                'min:1',
            ],
            'importe' => [
                $creating ? 'required' : 'sometimes',
                'integer',
                'min:1',
            ],
            'descuento' => [
                'sometimes',
                'integer',
                'min:0',
            ],
            'impuestos' => [
                'sometimes',
                'nullable',
                'array',
            ],
            'objetoImp' => [
                'sometimes',
                'string',
                'in:01,02,03,04',
            ],
            'numeroPedimento' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'cuentaPredial' => [
                'sometimes',
                'nullable',
                'array',
            ],
            'informacionAduanera' => [
                'sometimes',
                'nullable',
                'array',
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
            'cfdiInvoiceId.required' => 'La factura CFDI es obligatoria.',
            'cfdiInvoiceId.exists' => 'La factura CFDI no existe.',
            'numeroLinea.required' => 'El número de línea es obligatorio.',
            'numeroLinea.min' => 'El número de línea debe ser mayor a 0.',
            'claveProdServ.required' => 'La clave de producto/servicio es obligatoria.',
            'cantidad.required' => 'La cantidad es obligatoria.',
            'cantidad.min' => 'La cantidad debe ser mayor a 0.',
            'descripcion.required' => 'La descripción es obligatoria.',
            'valorUnitario.required' => 'El valor unitario es obligatorio.',
            'valorUnitario.min' => 'El valor unitario debe ser mayor a 0.',
            'importe.required' => 'El importe es obligatorio.',
            'importe.min' => 'El importe debe ser mayor a 0.',
            'objetoImp.in' => 'El objeto de impuesto debe ser 01, 02, 03 o 04.',
        ];
    }
}
