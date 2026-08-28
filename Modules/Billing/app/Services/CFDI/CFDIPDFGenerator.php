<?php

namespace Modules\Billing\Services\CFDI;

use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Models\CompanySetting;
use Modules\Billing\Models\DocumentLegend;
use Modules\Billing\Services\DocumentLegendRenderer;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;

/**
 * Generate PDF for CFDI invoices with QR code
 *
 * Creates professional PDF invoices compliant with SAT requirements
 * including QR code for verification
 */
class CFDIPDFGenerator
{
    use BuildsCfdiLegendContext;

    /**
     * Generate PDF for CFDI invoice
     *
     * @param CFDIInvoice $invoice
     * @return string Path to generated PDF
     */
    public function generate(CFDIInvoice $invoice): string
    {
        $settings = CompanySetting::where('is_active', true)->first();

        if (!$settings) {
            throw new \Exception('No se encontró configuración de facturación activa');
        }

        // Generate QR Code
        $qrData = $this->generateQRData($invoice, $settings);
        $qrCodeSvg = QrCode::size(150)
            ->margin(1)
            ->generate($qrData);

        // Convert SVG to base64 data URI for embedding in PDF
        $qrCodeBase64 = 'data:image/svg+xml;base64,' . base64_encode($qrCodeSvg);

        // Prepare data for view
        $data = [
            'invoice' => $invoice,
            'settings' => $settings,
            'qrCode' => $qrCodeBase64,
            'items' => $invoice->items,
            'totals' => $this->calculateTotals($invoice),
            'legendLines' => app(DocumentLegendRenderer::class)->render(
                DocumentLegend::TYPE_CFDI_INVOICE,
                $this->legendContext($invoice, $settings)
            ),
        ];

        // Generate PDF
        $pdf = Pdf::loadView('billing::cfdi-pdf', $data)
            ->setPaper('letter')
            ->setOption('defaultFont', 'DejaVu Sans');

        // Save PDF
        $filename = $this->generateFilename($invoice);
        $path = "cfdi/invoices/{$invoice->id}/{$filename}";

        Storage::disk('public')->put($path, $pdf->output());

        // Update invoice with PDF path
        $invoice->update([
            'pdf_path' => $path,
            'qr_code' => $qrCodeBase64,
        ]);

        return Storage::disk('public')->path($path);
    }

    /**
     * Generate QR code data according to SAT specification
     *
     * SAT QR Format:
     * https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx?
     * &id=UUID&re=RFC_EMISOR&rr=RFC_RECEPTOR&tt=TOTAL&fe=ULTIMOS_8_DIGITOS_SELLO
     *
     * @param CFDIInvoice $invoice
     * @param CompanySetting $settings
     * @return string QR code URL
     */
    protected function generateQRData(CFDIInvoice $invoice, CompanySetting $settings): string
    {
        if (!$invoice->uuid) {
            // For draft invoices, return placeholder
            return "https://verificacfdi.facturaelectronica.sat.gob.mx/";
        }

        $params = [
            'id' => $invoice->uuid,
            're' => $settings->rfc, // Use company RFC from settings
            'rr' => $invoice->receptor_rfc,
            'tt' => number_format($invoice->total / 100, 6, '.', ''), // Convert from cents
            'fe' => $this->extractSelloDigits($invoice),
        ];

        return "https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx?" . http_build_query($params);
    }

    /**
     * Extract last 8 digits of sello (digital seal) from XML
     *
     * @param CFDIInvoice $invoice
     * @return string Last 8 digits of sello or placeholder
     */
    protected function extractSelloDigits(CFDIInvoice $invoice): string
    {
        if (!$invoice->xml_timbrado) {
            return 'XXXXXXXX';
        }

        try {
            $xml = new \SimpleXMLElement($invoice->xml_timbrado);
            $xml->registerXPathNamespace('cfdi', 'http://www.sat.gob.mx/cfd/4');

            $sello = (string)$xml->xpath('//cfdi:Comprobante/@Sello')[0];

            if ($sello && strlen($sello) >= 8) {
                return substr($sello, -8);
            }
        } catch (\Exception $e) {
            \Log::warning('Could not extract sello from XML', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
        }

        return 'XXXXXXXX';
    }

    /**
     * Calculate invoice totals including taxes
     *
     * @param CFDIInvoice $invoice
     * @return array
     */
    protected function calculateTotals(CFDIInvoice $invoice): array
    {
        $subtotal = 0;
        $descuento = 0;
        $iva = 0;
        $ieps = 0;
        $isr_retenido = 0;
        $iva_retenido = 0;

        foreach ($invoice->items as $item) {
            $subtotal += $item->importe;
            $descuento += $item->descuento ?? 0;

            // Extract taxes from impuestos JSON
            $impuestosData = is_string($item->impuestos)
                ? json_decode($item->impuestos, true)
                : $item->impuestos;

            if (empty($impuestosData)) {
                continue;
            }

            // Traslados (IVA, IEPS)
            if (!empty($impuestosData['traslados'])) {
                foreach ($impuestosData['traslados'] as $traslado) {
                    switch ($traslado['impuesto']) {
                        case '002': // IVA
                            $iva += $traslado['importe'];
                            break;
                        case '003': // IEPS
                            $ieps += $traslado['importe'];
                            break;
                    }
                }
            }

            // Retenciones (ISR, IVA)
            if (!empty($impuestosData['retenciones'])) {
                foreach ($impuestosData['retenciones'] as $retencion) {
                    switch ($retencion['impuesto']) {
                        case '001': // ISR
                            $isr_retenido += $retencion['importe'];
                            break;
                        case '002': // IVA
                            $iva_retenido += $retencion['importe'];
                            break;
                    }
                }
            }
        }

        return [
            'subtotal' => $subtotal,
            'descuento' => $descuento,
            'iva' => $iva,
            'ieps' => $ieps,
            'isr_retenido' => $isr_retenido,
            'iva_retenido' => $iva_retenido,
            'total' => $invoice->total,
        ];
    }

    /**
     * Generate filename for PDF
     *
     * @param CFDIInvoice $invoice
     * @return string
     */
    protected function generateFilename(CFDIInvoice $invoice): string
    {
        $serie = $invoice->series ?? 'F';
        $folio = str_pad($invoice->folio, 6, '0', STR_PAD_LEFT);

        if ($invoice->uuid) {
            return "CFDI_{$serie}_{$folio}_{$invoice->uuid}.pdf";
        }

        return "CFDI_{$serie}_{$folio}_DRAFT.pdf";
    }

    /**
     * Format amount for display
     *
     * @param float|int $amount Amount in cents
     * @param string $currency Currency code
     * @return string Formatted amount
     */
    public function formatAmount($amount, string $currency = 'MXN'): string
    {
        // Convert from cents
        $amount = $amount / 100;

        $symbol = match($currency) {
            'MXN' => '$',
            'USD' => 'USD $',
            'EUR' => '€',
            default => $currency . ' ',
        };

        return $symbol . number_format($amount, 2);
    }

    /**
     * Download PDF as response
     *
     * @param CFDIInvoice $invoice
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function download(CFDIInvoice $invoice)
    {
        if (!$invoice->pdf_path) {
            // Generate PDF if not exists
            $this->generate($invoice);
        }

        $filename = $this->generateFilename($invoice);

        return Storage::disk('public')->download($invoice->pdf_path, $filename);
    }

    /**
     * Stream PDF inline (for preview)
     *
     * @param CFDIInvoice $invoice
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function preview(CFDIInvoice $invoice)
    {
        if (!$invoice->pdf_path) {
            // Generate PDF if not exists
            $this->generate($invoice);
        }

        return response()->file(
            Storage::disk('public')->path($invoice->pdf_path),
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline',
            ]
        );
    }
}
