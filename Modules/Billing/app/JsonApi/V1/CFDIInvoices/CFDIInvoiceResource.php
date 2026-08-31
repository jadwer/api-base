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
            // Barrido Paquete B 2026-08-31: el Resource manual pisa al
            // Schema; todo campo del Schema debe estar aqui o el API
            // guarda pero nunca lo devuelve.
            'companySettingId' => $this->company_setting_id,
            'contactId' => $this->contact_id,
            'arInvoiceId' => $this->ar_invoice_id,
            'fechaPago' => $this->fecha_pago,
            'montoPago' => $this->monto_pago,
            'formaPagoP' => $this->forma_pago_p,
            'arPaymentId' => $this->ar_payment_id,
            'numParcialidad' => $this->num_parcialidad,
            'impSaldoInsoluto' => $this->imp_saldo_insoluto,
            'pacResponse' => $this->pac_response,
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
