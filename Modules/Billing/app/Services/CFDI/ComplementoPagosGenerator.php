<?php

namespace Modules\Billing\Services\CFDI;

use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Models\CompanySetting;

/**
 * Generate a CFDI 4.0 tipo "P" (Pago) with the Complemento de Pagos 2.0.
 *
 * Namespace of the complement: http://www.sat.gob.mx/Pagos20 (XSD Pagos20.xsd).
 * Reuses Emisor/Receptor assembly via the EmisorReceptorBuilder trait, exactly
 * like CFDIXMLGenerator, so there is a single source of truth for those nodes.
 *
 * A CFDI tipo P carries SubTotal=0 / Total=0 and no MetodoPago/FormaPago at the
 * comprobante level (CFDI 4.0). The real payment lives in pago20:Pago, and the
 * documents it settles in pago20:DoctoRelacionado (rows of cfdi_payment_docs).
 *
 * v1 scope: single MXN payment to a single PPD invoice (Totales carries only the
 * MXN monto). Multimoneda (TipoCambioP / EquivalenciaDR) is v2.
 */
class ComplementoPagosGenerator
{
    use EmisorReceptorBuilder;

    public const PAGOS_NS = 'http://www.sat.gob.mx/Pagos20';

    /**
     * Build the CFDI tipo P XML for a REP.
     *
     * @param CFDIInvoice $paymentCfdi The CFDI tipo P (with paymentDocs loaded or loadable)
     * @return string XML content
     */
    public function generate(CFDIInvoice $paymentCfdi): string
    {
        $settings = $paymentCfdi->companySetting
            ?? CompanySetting::where('is_active', true)->first();

        if (!$settings) {
            throw new \Exception('No se encontró configuración de facturación activa');
        }

        $xml = new \DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;

        // Root: cfdi:Comprobante with both cfdi and pago20 namespaces
        $comprobante = $xml->createElementNS('http://www.sat.gob.mx/cfd/4', 'cfdi:Comprobante');
        $xml->appendChild($comprobante);

        $comprobante->setAttributeNS(
            'http://www.w3.org/2000/xmlns/',
            'xmlns:pago20',
            self::PAGOS_NS
        );

        $comprobante->setAttributeNS(
            'http://www.w3.org/2001/XMLSchema-instance',
            'xsi:schemaLocation',
            'http://www.sat.gob.mx/cfd/4 http://www.sat.gob.mx/sitio_internet/cfd/4/cfdv40.xsd '
            . self::PAGOS_NS . ' http://www.sat.gob.mx/sitio_internet/cfd/Pagos/Pagos20.xsd'
        );

        $this->addMainAttributes($comprobante, $paymentCfdi, $settings);
        $this->addEmisor($xml, $comprobante, $paymentCfdi, $settings);
        $this->addReceptor($xml, $comprobante, $paymentCfdi);
        $this->addConceptoPago($xml, $comprobante);
        $this->addComplementoPagos($xml, $comprobante, $paymentCfdi);

        return $xml->saveXML();
    }

    /**
     * Comprobante attributes for a CFDI tipo P.
     * SubTotal/Total = 0, Moneda = XXX, no MetodoPago/FormaPago (per CFDI 4.0).
     */
    protected function addMainAttributes(\DOMElement $comprobante, CFDIInvoice $invoice, CompanySetting $settings): void
    {
        $comprobante->setAttribute('Version', '4.0');
        $comprobante->setAttribute('Serie', $invoice->series ?? 'P');
        $comprobante->setAttribute('Folio', (string) $invoice->folio);
        $comprobante->setAttribute('Fecha', ($invoice->fecha_emision ?? now())->format('Y-m-d\TH:i:s'));
        $comprobante->setAttribute('SubTotal', '0');
        $comprobante->setAttribute('Moneda', 'XXX'); // Sin moneda para tipo P
        $comprobante->setAttribute('Total', '0');
        $comprobante->setAttribute('TipoDeComprobante', 'P');
        $comprobante->setAttribute('Exportacion', '01'); // No aplica
        $comprobante->setAttribute('LugarExpedicion', $settings->postal_code);

        if ($settings->certificate_number) {
            $comprobante->setAttribute('NoCertificado', $settings->certificate_number);
        }
    }

    /**
     * A CFDI tipo P requires a single Concepto placeholder (SAT-mandated values).
     */
    protected function addConceptoPago(\DOMDocument $xml, \DOMElement $comprobante): void
    {
        $conceptos = $xml->createElement('cfdi:Conceptos');
        $concepto = $xml->createElement('cfdi:Concepto');
        $concepto->setAttribute('ClaveProdServ', '84111506'); // Servicios de facturacion
        $concepto->setAttribute('Cantidad', '1');
        $concepto->setAttribute('ClaveUnidad', 'ACT'); // Actividad
        $concepto->setAttribute('Descripcion', 'Pago');
        $concepto->setAttribute('ValorUnitario', '0');
        $concepto->setAttribute('Importe', '0');
        $concepto->setAttribute('ObjetoImp', '01'); // No objeto de impuesto
        $conceptos->appendChild($concepto);
        $comprobante->appendChild($conceptos);
    }

    /**
     * Build cfdi:Complemento > pago20:Pagos > (Totales, Pago > DoctoRelacionado[]).
     */
    protected function addComplementoPagos(\DOMDocument $xml, \DOMElement $comprobante, CFDIInvoice $invoice): void
    {
        $docs = $invoice->relationLoaded('paymentDocs')
            ? $invoice->getRelation('paymentDocs')
            : $invoice->paymentDocs()->get();

        $montoPago = $this->pesos($invoice->monto_pago ?? 0);

        $complemento = $xml->createElement('cfdi:Complemento');
        $comprobante->appendChild($complemento);

        $pagos = $xml->createElementNS(self::PAGOS_NS, 'pago20:Pagos');
        $pagos->setAttribute('Version', '2.0');
        $complemento->appendChild($pagos);

        // Totales: v1 misma moneda MXN => solo MontoTotalPagos
        $totales = $xml->createElement('pago20:Totales');
        $totales->setAttribute('MontoTotalPagos', $montoPago);
        $pagos->appendChild($totales);

        // Pago
        $pago = $xml->createElement('pago20:Pago');
        $pago->setAttribute('FechaPago', ($invoice->fecha_pago ?? now())->format('Y-m-d\TH:i:s'));
        $pago->setAttribute('FormaDePagoP', $invoice->forma_pago_p ?? '99');
        $pago->setAttribute('MonedaP', 'MXN');
        $pago->setAttribute('TipoCambioP', '1'); // v1 misma moneda MXN
        $pago->setAttribute('Monto', $montoPago);
        $pagos->appendChild($pago);

        foreach ($docs as $doc) {
            $docto = $xml->createElement('pago20:DoctoRelacionado');
            $docto->setAttribute('IdDocumento', $doc->related_uuid);
            if ($doc->serie) {
                $docto->setAttribute('Serie', $doc->serie);
            }
            if ($doc->folio) {
                $docto->setAttribute('Folio', $doc->folio);
            }
            $docto->setAttribute('MonedaDR', $doc->moneda_dr ?? 'MXN');
            $docto->setAttribute('EquivalenciaDR', '1'); // v1 misma moneda MXN
            $docto->setAttribute('NumParcialidad', (string) $doc->num_parcialidad);
            $docto->setAttribute('ImpSaldoAnt', $this->pesos($doc->imp_saldo_ant));
            $docto->setAttribute('ImpPagado', $this->pesos($doc->imp_pagado));
            $docto->setAttribute('ImpSaldoInsoluto', $this->pesos($doc->imp_saldo_insoluto));
            $docto->setAttribute('ObjetoImpDR', $doc->objeto_imp_dr ?? '01');
            $pago->appendChild($docto);
        }
    }

    /**
     * Cents (integer) -> peso string with 2 decimals, SAT format.
     */
    protected function pesos(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
