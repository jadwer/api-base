<?php

namespace Modules\Billing\JsonApi\V1\CFDIInvoices;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

class CFDIInvoiceResource extends JsonApiResource
{
    /**
     * Get the resource's attributes.
     *
     * @param Request|null $request
     * @return iterable
     */
    public function attributes($request): iterable
    {
        return [
            'series' => $this->series,
            'folio' => $this->folio,
            'uuid' => $this->uuid,
            'tipoComprobante' => $this->tipo_comprobante,
            'receptorRfc' => $this->receptor_rfc,
            'receptorNombre' => $this->receptor_nombre,
            'receptorUsoCfdi' => $this->receptor_uso_cfdi,
            'receptorRegimenFiscal' => $this->receptor_regimen_fiscal,
            'receptorDomicilioFiscal' => $this->receptor_domicilio_fiscal,
            'subtotal' => $this->subtotal,
            'total' => $this->total,
            'descuento' => $this->descuento,
            'iva' => $this->iva,
            'ieps' => $this->ieps,
            'isrRetenido' => $this->isr_retenido,
            'ivaRetenido' => $this->iva_retenido,
            'moneda' => $this->moneda,
            'tipoCambio' => $this->tipo_cambio,
            'formaPago' => $this->forma_pago,
            'metodoPago' => $this->metodo_pago,
            'condicionesPago' => $this->condiciones_pago,
            'cfdiRelacionadoTipo' => $this->cfdi_relacionado_tipo,
            'cfdiRelacionadoUuids' => $this->cfdi_relacionado_uuids,
            'status' => $this->status,
            'fechaEmision' => $this->fecha_emision,
            'fechaTimbrado' => $this->fecha_timbrado,
            'fechaCancelacion' => $this->fecha_cancelacion,
            'xmlPath' => $this->xml_path,
            'pdfPath' => $this->pdf_path,
            'errorMessage' => $this->error_message,
            'metadata' => $this->metadata,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }

    /**
     * Get the resource's relationships.
     *
     * @param Request|null $request
     * @return iterable
     */
    public function relationships($request): iterable
    {
        return [
            $this->relation('companySetting'),
            $this->relation('contact'),
            $this->relation('arInvoice'),
            $this->relation('items'),
        ];
    }
}
