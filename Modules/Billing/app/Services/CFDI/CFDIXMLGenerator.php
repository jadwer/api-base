<?php

namespace Modules\Billing\Services\CFDI;

use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Models\CompanySetting;

/**
 * Generate CFDI 4.0 XML compliant with SAT specifications
 *
 * This service generates valid XML for Mexican electronic invoices (CFDI)
 * according to SAT (Servicio de Administración Tributaria) requirements.
 */
class CFDIXMLGenerator
{
    /**
     * Generate CFDI 4.0 XML from invoice data
     *
     * @param CFDIInvoice $invoice
     * @return string XML content
     */
    public function generate(CFDIInvoice $invoice): string
    {
        $settings = CompanySetting::where('is_active', true)->first();

        if (!$settings) {
            throw new \Exception('No se encontró configuración de facturación activa');
        }

        // Create XML with namespaces
        $xml = new \DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;

        // Root element: cfdi:Comprobante
        $comprobante = $xml->createElementNS('http://www.sat.gob.mx/cfd/4', 'cfdi:Comprobante');
        $xml->appendChild($comprobante);

        // Add schema location
        $comprobante->setAttributeNS(
            'http://www.w3.org/2001/XMLSchema-instance',
            'xsi:schemaLocation',
            'http://www.sat.gob.mx/cfd/4 http://www.sat.gob.mx/sitio_internet/cfd/4/cfdv40.xsd'
        );

        // Add main attributes
        $this->addMainAttributes($comprobante, $invoice, $settings);

        // Add Emisor (Issuer - our company)
        $this->addEmisor($xml, $comprobante, $invoice, $settings);

        // Add Receptor (Receiver - customer)
        $this->addReceptor($xml, $comprobante, $invoice);

        // Add Conceptos (line items)
        $this->addConceptos($xml, $comprobante, $invoice);

        // Add Impuestos (taxes summary)
        $this->addImpuestos($xml, $comprobante, $invoice);

        // Add related CFDIs if any
        if (!empty($invoice->cfdi_relacionado_uuids)) {
            $this->addCfdiRelacionados($xml, $comprobante, $invoice);
        }

        return $xml->saveXML();
    }

    /**
     * Add main attributes to Comprobante element
     */
    protected function addMainAttributes(\DOMElement $comprobante, CFDIInvoice $invoice, CompanySetting $settings): void
    {
        $comprobante->setAttribute('Version', '4.0');
        $comprobante->setAttribute('Serie', $invoice->series ?? 'F');
        $comprobante->setAttribute('Folio', (string)$invoice->folio);
        $comprobante->setAttribute('Fecha', $invoice->fecha_emision->format('Y-m-d\TH:i:s'));
        $comprobante->setAttribute('FormaPago', $invoice->forma_pago ?? '99');
        $comprobante->setAttribute('SubTotal', $this->formatAmount($invoice->subtotal));

        if ($invoice->descuento > 0) {
            $comprobante->setAttribute('Descuento', $this->formatAmount($invoice->descuento));
        }

        $comprobante->setAttribute('Moneda', $invoice->moneda ?? 'MXN');

        if ($invoice->moneda !== 'MXN' && $invoice->tipo_cambio) {
            $comprobante->setAttribute('TipoCambio', number_format($invoice->tipo_cambio, 6, '.', ''));
        }

        $comprobante->setAttribute('Total', $this->formatAmount($invoice->total));
        $comprobante->setAttribute('TipoDeComprobante', $invoice->tipo_comprobante ?? 'I');
        $comprobante->setAttribute('MetodoPago', $invoice->metodo_pago ?? 'PUE');
        $comprobante->setAttribute('LugarExpedicion', $settings->postal_code);

        // Certificate info (will be added during stamping)
        if ($settings->certificate_number) {
            $comprobante->setAttribute('NoCertificado', $settings->certificate_number);
        }

        // Exportacion attribute (required in CFDI 4.0)
        $comprobante->setAttribute('Exportacion', '01'); // No aplica
    }

    /**
     * Add Emisor element
     */
    protected function addEmisor(\DOMDocument $xml, \DOMElement $comprobante, CFDIInvoice $invoice, CompanySetting $settings): void
    {
        $emisor = $xml->createElement('cfdi:Emisor');
        $emisor->setAttribute('Rfc', $invoice->emisor_rfc ?? $settings->rfc);
        $emisor->setAttribute('Nombre', $invoice->emisor_nombre ?? $settings->legal_name);
        $emisor->setAttribute('RegimenFiscal', $invoice->emisor_regimen_fiscal ?? $settings->fiscal_regime);
        $comprobante->appendChild($emisor);
    }

    /**
     * Add Receptor element
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

    /**
     * Add Conceptos (line items) element
     */
    protected function addConceptos(\DOMDocument $xml, \DOMElement $comprobante, CFDIInvoice $invoice): void
    {
        $conceptos = $xml->createElement('cfdi:Conceptos');

        foreach ($invoice->items as $index => $item) {
            $concepto = $xml->createElement('cfdi:Concepto');

            $concepto->setAttribute('ClaveProdServ', $item->clave_prod_serv ?? '01010101');

            if ($item->no_identificacion) {
                $concepto->setAttribute('NoIdentificacion', $item->no_identificacion);
            }

            $concepto->setAttribute('Cantidad', number_format($item->cantidad, 6, '.', ''));
            $concepto->setAttribute('ClaveUnidad', $item->clave_unidad ?? 'E48');

            if ($item->unidad) {
                $concepto->setAttribute('Unidad', $item->unidad);
            }

            $concepto->setAttribute('Descripcion', $item->descripcion);
            $concepto->setAttribute('ValorUnitario', $this->formatAmount($item->valor_unitario));
            $concepto->setAttribute('Importe', $this->formatAmount($item->importe));

            if ($item->descuento > 0) {
                $concepto->setAttribute('Descuento', $this->formatAmount($item->descuento));
            }

            $concepto->setAttribute('ObjetoImp', $item->objeto_imp ?? '02');

            // Add taxes for this item (from impuestos JSON field)
            if (!empty($item->impuestos)) {
                $this->addConceptoImpuestos($xml, $concepto, $item);
            }

            $conceptos->appendChild($concepto);
        }

        $comprobante->appendChild($conceptos);
    }

    /**
     * Add taxes to a Concepto element
     */
    protected function addConceptoImpuestos(\DOMDocument $xml, \DOMElement $concepto, $item): void
    {
        $impuestosData = is_string($item->impuestos) ? json_decode($item->impuestos, true) : $item->impuestos;

        if (empty($impuestosData)) {
            return;
        }

        $impuestos = $xml->createElement('cfdi:Impuestos');

        // Traslados (taxes charged to customer - like IVA)
        if (!empty($impuestosData['traslados'])) {
            $trasladosNode = $xml->createElement('cfdi:Traslados');

            foreach ($impuestosData['traslados'] as $traslado) {
                $trasladoElement = $xml->createElement('cfdi:Traslado');
                $trasladoElement->setAttribute('Base', $this->formatAmount($traslado['base']));
                $trasladoElement->setAttribute('Impuesto', $traslado['impuesto']);
                $trasladoElement->setAttribute('TipoFactor', $traslado['tipo_factor']);
                $trasladoElement->setAttribute('TasaOCuota', number_format($traslado['tasa_o_cuota'], 6, '.', ''));
                $trasladoElement->setAttribute('Importe', $this->formatAmount($traslado['importe']));
                $trasladosNode->appendChild($trasladoElement);
            }

            $impuestos->appendChild($trasladosNode);
        }

        // Retenciones (taxes withheld - like ISR)
        if (!empty($impuestosData['retenciones'])) {
            $retencionesNode = $xml->createElement('cfdi:Retenciones');

            foreach ($impuestosData['retenciones'] as $retencion) {
                $retencionElement = $xml->createElement('cfdi:Retencion');
                $retencionElement->setAttribute('Base', $this->formatAmount($retencion['base']));
                $retencionElement->setAttribute('Impuesto', $retencion['impuesto']);
                $retencionElement->setAttribute('TipoFactor', $retencion['tipo_factor']);
                $retencionElement->setAttribute('TasaOCuota', number_format($retencion['tasa_o_cuota'], 6, '.', ''));
                $retencionElement->setAttribute('Importe', $this->formatAmount($retencion['importe']));
                $retencionesNode->appendChild($retencionElement);
            }

            $impuestos->appendChild($retencionesNode);
        }

        $concepto->appendChild($impuestos);
    }

    /**
     * Add Impuestos (taxes summary) element
     */
    protected function addImpuestos(\DOMDocument $xml, \DOMElement $comprobante, CFDIInvoice $invoice): void
    {
        // Calculate totals from all item taxes
        $totalTraslados = 0;
        $totalRetenciones = 0;
        $trasladosGrouped = [];
        $retencionesGrouped = [];

        foreach ($invoice->items as $item) {
            $impuestosData = is_string($item->impuestos) ? json_decode($item->impuestos, true) : $item->impuestos;

            if (empty($impuestosData)) {
                continue;
            }

            // Process traslados
            if (!empty($impuestosData['traslados'])) {
                foreach ($impuestosData['traslados'] as $traslado) {
                    $totalTraslados += $traslado['importe'];

                    // Group by impuesto and tasa
                    $key = $traslado['impuesto'] . '_' . $traslado['tipo_factor'] . '_' . $traslado['tasa_o_cuota'];
                    if (!isset($trasladosGrouped[$key])) {
                        $trasladosGrouped[$key] = [
                            'impuesto' => $traslado['impuesto'],
                            'tipo_factor' => $traslado['tipo_factor'],
                            'tasa_o_cuota' => $traslado['tasa_o_cuota'],
                            'base' => 0,
                            'importe' => 0,
                        ];
                    }
                    $trasladosGrouped[$key]['base'] += $traslado['base'];
                    $trasladosGrouped[$key]['importe'] += $traslado['importe'];
                }
            }

            // Process retenciones
            if (!empty($impuestosData['retenciones'])) {
                foreach ($impuestosData['retenciones'] as $retencion) {
                    $totalRetenciones += $retencion['importe'];

                    // Group by impuesto
                    $key = $retencion['impuesto'];
                    if (!isset($retencionesGrouped[$key])) {
                        $retencionesGrouped[$key] = [
                            'impuesto' => $retencion['impuesto'],
                            'importe' => 0,
                        ];
                    }
                    $retencionesGrouped[$key]['importe'] += $retencion['importe'];
                }
            }
        }

        // Only add Impuestos node if there are taxes
        if ($totalTraslados > 0 || $totalRetenciones > 0) {
            $impuestos = $xml->createElement('cfdi:Impuestos');

            if ($totalRetenciones > 0) {
                $impuestos->setAttribute('TotalImpuestosRetenidos', $this->formatAmount($totalRetenciones));

                // Add Retenciones node
                $retencionesNode = $xml->createElement('cfdi:Retenciones');
                foreach ($retencionesGrouped as $retencion) {
                    $retencionElement = $xml->createElement('cfdi:Retencion');
                    $retencionElement->setAttribute('Impuesto', $retencion['impuesto']);
                    $retencionElement->setAttribute('Importe', $this->formatAmount($retencion['importe']));
                    $retencionesNode->appendChild($retencionElement);
                }
                $impuestos->appendChild($retencionesNode);
            }

            if ($totalTraslados > 0) {
                $impuestos->setAttribute('TotalImpuestosTrasladados', $this->formatAmount($totalTraslados));

                // Add Traslados node
                $trasladosNode = $xml->createElement('cfdi:Traslados');
                foreach ($trasladosGrouped as $traslado) {
                    $trasladoElement = $xml->createElement('cfdi:Traslado');
                    $trasladoElement->setAttribute('Base', $this->formatAmount($traslado['base']));
                    $trasladoElement->setAttribute('Impuesto', $traslado['impuesto']);
                    $trasladoElement->setAttribute('TipoFactor', $traslado['tipo_factor']);
                    $trasladoElement->setAttribute('TasaOCuota', number_format($traslado['tasa_o_cuota'], 6, '.', ''));
                    $trasladoElement->setAttribute('Importe', $this->formatAmount($traslado['importe']));
                    $trasladosNode->appendChild($trasladoElement);
                }
                $impuestos->appendChild($trasladosNode);
            }

            $comprobante->appendChild($impuestos);
        }
    }

    /**
     * Add CfdiRelacionados element
     */
    protected function addCfdiRelacionados(\DOMDocument $xml, \DOMElement $comprobante, CFDIInvoice $invoice): void
    {
        $uuids = is_string($invoice->cfdi_relacionado_uuids)
            ? json_decode($invoice->cfdi_relacionado_uuids, true)
            : $invoice->cfdi_relacionado_uuids;

        if (empty($uuids) || !$invoice->cfdi_relacionado_tipo) {
            return;
        }

        $relacionados = $xml->createElement('cfdi:CfdiRelacionados');
        $relacionados->setAttribute('TipoRelacion', $invoice->cfdi_relacionado_tipo);

        foreach ($uuids as $uuid) {
            $relacionado = $xml->createElement('cfdi:CfdiRelacionado');
            $relacionado->setAttribute('UUID', $uuid);
            $relacionados->appendChild($relacionado);
        }

        $comprobante->appendChild($relacionados);
    }

    /**
     * Format amount to SAT specification (6 decimals)
     */
    protected function formatAmount($amount): string
    {
        // Convert from cents to currency if needed
        if ($amount >= 100) {
            $amount = $amount / 100;
        }

        return number_format($amount, 2, '.', '');
    }

    /**
     * Validate XML against SAT XSD schema
     *
     * @param string $xml
     * @return bool
     * @throws \Exception
     */
    public function validate(string $xml): bool
    {
        $dom = new \DOMDocument();
        $dom->loadXML($xml);

        // Note: In production, download XSD from SAT and validate
        // For now, just check if it's valid XML
        if (!$dom->schemaValidate(__DIR__ . '/../../../Resources/xsd/cfdv40.xsd')) {
            $errors = libxml_get_errors();
            libxml_clear_errors();

            $errorMessages = array_map(function($error) {
                return $error->message;
            }, $errors);

            throw new \Exception('XML validation failed: ' . implode(', ', $errorMessages));
        }

        return true;
    }
}
