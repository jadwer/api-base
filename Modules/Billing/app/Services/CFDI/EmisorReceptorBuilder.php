<?php

namespace Modules\Billing\Services\CFDI;

use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Models\CompanySetting;

/**
 * Shared CFDI 4.0 Emisor/Receptor assembly.
 *
 * Extracted from CFDIXMLGenerator so that both the income CFDI generator and
 * the Complemento de Pagos generator (ComplementoPagosGenerator) build the
 * cfdi:Emisor and cfdi:Receptor nodes identically, without duplicating the
 * SAT-mandated attribute logic.
 */
trait EmisorReceptorBuilder
{
    /**
     * Add Emisor element (Issuer - our company)
     */
    protected function addEmisor(\DOMDocument $xml, \DOMElement $comprobante, CFDIInvoice $invoice, CompanySetting $settings): void
    {
        $emisor = $xml->createElement('cfdi:Emisor');
        $emisor->setAttribute('Rfc', $invoice->emisor_rfc ?? $settings->rfc);
        $emisor->setAttribute('Nombre', $invoice->emisor_nombre ?? $settings->company_name);
        $emisor->setAttribute('RegimenFiscal', $invoice->emisor_regimen_fiscal ?? $settings->tax_regime);
        $comprobante->appendChild($emisor);
    }

    /**
     * Add Receptor element (Receiver - customer)
     */
    protected function addReceptor(\DOMDocument $xml, \DOMElement $comprobante, CFDIInvoice $invoice): void
    {
        $receptor = $xml->createElement('cfdi:Receptor');
        $receptor->setAttribute('Rfc', $invoice->receptor_rfc);
        $receptor->setAttribute('Nombre', $invoice->receptor_nombre);
        $receptor->setAttribute('DomicilioFiscalReceptor', $invoice->receptor_domicilio_fiscal ?? '00000');
        $receptor->setAttribute('RegimenFiscalReceptor', $invoice->receptor_regimen_fiscal ?? '616');
        $receptor->setAttribute('UsoCFDI', $invoice->receptor_uso_cfdi ?? 'G03');
        $comprobante->appendChild($receptor);
    }
}
