<?php

namespace Modules\Billing\Services\CFDI;

use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Models\CompanySetting;
use Modules\Sales\Models\SalesOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * Generate Prefactura PDF (Invoice Preview without SAT stamping)
 *
 * Creates a preview PDF with "PREFACTURA" watermark for review
 * before sending to PAC for official stamping.
 */
class PrefacturaPDFGenerator
{
    /**
     * Generate prefactura PDF from an existing CFDIInvoice in draft status
     *
     * @param CFDIInvoice $invoice
     * @return string Path to generated PDF
     */
    public function generate(CFDIInvoice $invoice): string
    {
        $settings = CompanySetting::where('is_active', true)->first();

        if (!$settings) {
            throw new \Exception('No se encontro configuracion de facturacion activa');
        }

        // Prepare data for view
        $data = [
            'invoice' => $invoice,
            'settings' => $settings,
            'items' => $invoice->items,
            'totals' => $this->calculateTotals($invoice),
        ];

        // Generate PDF
        $pdf = Pdf::loadView('billing::prefactura-pdf', $data)
            ->setPaper('letter')
            ->setOption('defaultFont', 'DejaVu Sans');

        // Save PDF
        $filename = $this->generateFilename($invoice);
        $path = "cfdi/prefacturas/{$invoice->id}/{$filename}";

        Storage::disk('public')->put($path, $pdf->output());

        return Storage::disk('public')->path($path);
    }

    /**
     * Generate prefactura preview from a SalesOrder (without creating CFDIInvoice)
     *
     * This allows previewing how the invoice would look before creating the CFDI record.
     *
     * @param SalesOrder $order
     * @param array $cfdiData Optional CFDI data overrides (receptor_rfc, uso_cfdi, etc.)
     * @return string PDF content as string
     */
    public function generateFromOrder(SalesOrder $order, array $cfdiData = []): string
    {
        $settings = CompanySetting::where('is_active', true)->first();

        if (!$settings) {
            throw new \Exception('No se encontro configuracion de facturacion activa');
        }

        // Build pseudo-invoice object from SalesOrder
        $invoice = $this->buildPseudoInvoice($order, $cfdiData, $settings);
        $items = $this->buildPseudoItems($order);

        $data = [
            'invoice' => $invoice,
            'settings' => $settings,
            'items' => $items,
            'totals' => [
                'subtotal' => (int)($order->subtotal * 100),
                'descuento' => (int)(($order->discount_total ?? 0) * 100),
                'iva' => (int)($order->tax_amount * 100),
                'ieps' => 0,
                'isr_retenido' => 0,
                'iva_retenido' => 0,
                'total' => (int)($order->total_amount * 100),
            ],
        ];

        $pdf = Pdf::loadView('billing::prefactura-pdf', $data)
            ->setPaper('letter')
            ->setOption('defaultFont', 'DejaVu Sans');

        return $pdf->output();
    }

    /**
     * Build pseudo-invoice object from SalesOrder for preview
     */
    protected function buildPseudoInvoice(SalesOrder $order, array $cfdiData, CompanySetting $settings): object
    {
        $contact = $order->contact;

        return (object)[
            'id' => $order->id,
            'series' => $cfdiData['series'] ?? $settings->invoice_series ?? 'F',
            'folio' => $cfdiData['folio'] ?? $settings->next_invoice_folio ?? 1,
            'receptor_nombre' => $contact->business_name ?? $contact->full_name ?? 'Publico en General',
            'receptor_rfc' => $cfdiData['receptor_rfc'] ?? $contact->rfc ?? 'XAXX010101000',
            'receptor_uso_cfdi' => $cfdiData['receptor_uso_cfdi'] ?? 'G03',
            'receptor_regimen_fiscal' => $cfdiData['receptor_regimen_fiscal'] ?? $contact->tax_regime ?? '616',
            'receptor_domicilio_fiscal' => $cfdiData['receptor_domicilio_fiscal'] ?? $contact->postal_code ?? $settings->postal_code,
            'fecha_emision' => now(),
            'tipo_comprobante' => 'I',
            'metodo_pago' => $cfdiData['metodo_pago'] ?? 'PUE',
            'forma_pago' => $cfdiData['forma_pago'] ?? '01',
            'moneda' => $order->metadata['currency'] ?? 'MXN',
            'total' => (int)($order->total_amount * 100),
            'uuid' => null,
        ];
    }

    /**
     * Build pseudo-items from SalesOrder items
     */
    protected function buildPseudoItems(SalesOrder $order): array
    {
        $items = [];

        foreach ($order->items as $item) {
            $product = $item->product;

            $items[] = (object)[
                'clave_prod_serv' => $product->sat_product_code ?? '01010101',
                'descripcion' => $item->product_name ?? $product->name,
                'cantidad' => $item->quantity,
                'clave_unidad' => $product->sat_unit_code ?? 'H87',
                'valor_unitario' => (int)($item->unit_price * 100),
                'importe' => (int)($item->subtotal * 100),
            ];
        }

        return $items;
    }

    /**
     * Calculate invoice totals
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

            $impuestosData = is_string($item->impuestos)
                ? json_decode($item->impuestos, true)
                : $item->impuestos;

            if (empty($impuestosData)) {
                continue;
            }

            if (!empty($impuestosData['traslados'])) {
                foreach ($impuestosData['traslados'] as $traslado) {
                    switch ($traslado['impuesto']) {
                        case '002':
                            $iva += $traslado['importe'];
                            break;
                        case '003':
                            $ieps += $traslado['importe'];
                            break;
                    }
                }
            }

            if (!empty($impuestosData['retenciones'])) {
                foreach ($impuestosData['retenciones'] as $retencion) {
                    switch ($retencion['impuesto']) {
                        case '001':
                            $isr_retenido += $retencion['importe'];
                            break;
                        case '002':
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
     * Generate filename for prefactura PDF
     */
    protected function generateFilename(CFDIInvoice $invoice): string
    {
        $serie = $invoice->series ?? 'F';
        $folio = str_pad($invoice->folio, 6, '0', STR_PAD_LEFT);
        $timestamp = now()->format('YmdHis');

        return "PREFACTURA_{$serie}_{$folio}_{$timestamp}.pdf";
    }

    /**
     * Download prefactura as response
     *
     * @param CFDIInvoice $invoice
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function download(CFDIInvoice $invoice)
    {
        $path = $this->generate($invoice);
        $filename = $this->generateFilename($invoice);

        return response()->download($path, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Stream prefactura inline (for preview in browser)
     *
     * @param CFDIInvoice $invoice
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function preview(CFDIInvoice $invoice)
    {
        $path = $this->generate($invoice);

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline',
        ]);
    }

    /**
     * Stream prefactura from SalesOrder (without saving)
     *
     * @param SalesOrder $order
     * @param array $cfdiData
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function streamFromOrder(SalesOrder $order, array $cfdiData = [])
    {
        $content = $this->generateFromOrder($order, $cfdiData);

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="PREFACTURA_OV_' . $order->order_number . '.pdf"',
        ]);
    }

    /**
     * Download prefactura from SalesOrder
     *
     * @param SalesOrder $order
     * @param array $cfdiData
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function downloadFromOrder(SalesOrder $order, array $cfdiData = [])
    {
        $content = $this->generateFromOrder($order, $cfdiData);

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="PREFACTURA_OV_' . $order->order_number . '.pdf"',
        ]);
    }
}
