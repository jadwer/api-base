<?php

namespace Modules\Sales\Services;

use Modules\Sales\Models\SalesOrder;
use Modules\Billing\Models\CompanySetting;
use Modules\Billing\Models\DocumentLegend;
use Modules\Billing\Services\DocumentLegendRenderer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Luecano\NumeroALetras\NumeroALetras;

/**
 * SA-M011: Enhanced PDF Generator for Sales Orders
 *
 * Creates professional PDF order documents with company branding,
 * bank accounts, and commercial conditions - following the same
 * format as quotes and invoices.
 */
class SalesOrderPDFGenerator
{
    /**
     * Status labels in Spanish
     */
    protected const STATUS_LABELS = [
        'draft' => 'Borrador',
        'pending' => 'Pendiente',
        'confirmed' => 'Confirmado',
        'processing' => 'En Proceso',
        'shipped' => 'Enviado',
        'delivered' => 'Entregado',
        'cancelled' => 'Cancelado',
    ];

    /**
     * Generate PDF for sales order
     *
     * @param SalesOrder $order
     * @param array $options Optional generation options
     * @return string Path to generated PDF
     */
    public function generate(SalesOrder $order, array $options = []): string
    {
        $settings = CompanySetting::getActive();

        $data = $this->prepareData($order, $settings, $options);

        $pdf = Pdf::loadView('sales::sales-order-pdf', $data)
            ->setPaper('letter')
            ->setOption('defaultFont', 'DejaVu Sans');

        $filename = $this->generateFilename($order);
        $path = "orders/{$order->id}/{$filename}";

        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }

    /**
     * Prepare data for PDF view
     */
    protected function prepareData(SalesOrder $order, ?CompanySetting $settings, array $options): array
    {
        $order->load(['items.product.unit', 'contact']);

        $totals = $this->calculateTotals($order);
        $currency = $order->metadata['currency'] ?? 'MXN';

        // Generate total in words
        $totalInWords = null;
        if (class_exists(NumeroALetras::class)) {
            try {
                $formatter = new NumeroALetras();
                $totalInWords = $formatter->toInvoice(
                    $totals['total'],
                    2,
                    $this->getCurrencyName($currency)
                );
            } catch (\Exception $e) {
                $totalInWords = null;
            }
        }

        // Determine what sections to show
        $showBankAccounts = $options['show_bank_accounts']
            ?? $settings?->getQuoteSettings()['show_bank_accounts']
            ?? true;
        $showConditions = $options['show_conditions']
            ?? $settings?->getQuoteSettings()['show_commercial_conditions']
            ?? true;

        // Leyenda configurable por tipo de documento (fallback: commercial_conditions)
        $legendLines = app(DocumentLegendRenderer::class)->render(DocumentLegend::TYPE_SALES_ORDER, [
            'folio' => $order->order_number,
            'fecha_emision' => $order->order_date?->format('d/m/Y') ?? $order->created_at?->format('d/m/Y'),
            'fecha_vencimiento' => '',
            'total' => '$' . number_format((float) $totals['total'], 2) . ' ' . $currency,
            'total_letra' => $totalInWords,
            'cliente' => $order->contact?->name,
            'rfc_cliente' => $order->contact?->tax_id,
            'empresa' => $settings?->company_name,
            'dias_credito' => $order->credit_days ?? $order->contact?->payment_terms,
        ]);

        return [
            'order' => $order,
            'items' => $order->items,
            'contact' => $order->contact,
            'totals' => $totals,
            'settings' => $settings,
            'shippingAddress' => $order->shipping_address,
            'billingAddress' => $order->billing_address,
            'currency' => $currency,
            'totalInWords' => $totalInWords,
            'statusLabels' => self::STATUS_LABELS,
            'showBankAccounts' => $showBankAccounts,
            'showConditions' => $showConditions,
            'conditions' => $legendLines ?? $settings?->getCommercialConditions() ?? [],
        ];
    }

    /**
     * Calculate order totals
     */
    protected function calculateTotals(SalesOrder $order): array
    {
        $subtotal = $order->subtotal ?? 0;
        $tax = $order->tax_amount ?? 0;
        $shipping = $order->shipping_amount ?? 0;
        $discount = $order->discount_total ?? 0;

        // If subtotal not set, calculate from items
        if ($subtotal == 0 && $order->items->isNotEmpty()) {
            $subtotal = $order->items->sum(function ($item) {
                return $item->quantity * $item->unit_price;
            });
        }

        return [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping' => $shipping,
            'discount' => $discount,
            'total' => $order->total_amount,
        ];
    }

    /**
     * Get currency name for amount in words
     */
    protected function getCurrencyName(string $code): string
    {
        return match (strtoupper($code)) {
            'MXN' => 'PESOS MEXICANOS',
            'USD' => 'DOLARES AMERICANOS',
            'EUR' => 'EUROS',
            default => $code,
        };
    }

    /**
     * Generate filename for PDF
     */
    protected function generateFilename(SalesOrder $order): string
    {
        $number = str_replace(['/', '\\'], '-', $order->order_number);
        return "OV_{$number}.pdf";
    }

    /**
     * Download PDF as response
     *
     * @param SalesOrder $order
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function download(SalesOrder $order)
    {
        $path = $this->getOrGeneratePath($order);
        $filename = $this->generateFilename($order);

        return Storage::disk('public')->download($path, $filename);
    }

    /**
     * Stream PDF inline (for preview)
     *
     * @param SalesOrder $order
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function preview(SalesOrder $order)
    {
        $path = $this->getOrGeneratePath($order);

        return response()->file(
            Storage::disk('public')->path($path),
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline',
            ]
        );
    }

    /**
     * Stream PDF without storing
     *
     * @param SalesOrder $order
     * @param array $options
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function stream(SalesOrder $order, array $options = [])
    {
        $settings = CompanySetting::getActive();
        $data = $this->prepareData($order, $settings, $options);

        $pdf = Pdf::loadView('sales::sales-order-pdf', $data)
            ->setPaper('letter')
            ->setOption('defaultFont', 'DejaVu Sans');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $this->generateFilename($order) . '"',
        ]);
    }

    /**
     * Get existing PDF path or generate new one
     */
    protected function getOrGeneratePath(SalesOrder $order): string
    {
        $expectedPath = "orders/{$order->id}/" . $this->generateFilename($order);

        if (!Storage::disk('public')->exists($expectedPath)) {
            return $this->generate($order);
        }

        return $expectedPath;
    }

    /**
     * Regenerate PDF (force new generation)
     *
     * @param SalesOrder $order
     * @param array $options
     * @return string Path to generated PDF
     */
    public function regenerate(SalesOrder $order, array $options = []): string
    {
        // Delete existing PDF if exists
        $this->delete($order);

        return $this->generate($order, $options);
    }

    /**
     * Delete existing PDF
     *
     * @param SalesOrder $order
     * @return bool
     */
    public function delete(SalesOrder $order): bool
    {
        $path = "orders/{$order->id}/" . $this->generateFilename($order);

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }

        return false;
    }

    /**
     * Get PDF URL if exists
     *
     * @param SalesOrder $order
     * @return string|null
     */
    public function getUrl(SalesOrder $order): ?string
    {
        $path = "orders/{$order->id}/" . $this->generateFilename($order);

        if (Storage::disk('public')->exists($path)) {
            return asset('storage/' . $path);
        }

        return null;
    }

    /**
     * Format amount for display
     *
     * @param float $amount
     * @param string $currency
     * @return string
     */
    public function formatAmount(float $amount, string $currency = 'MXN'): string
    {
        $symbol = match ($currency) {
            'MXN' => '$',
            'USD' => 'USD $',
            'EUR' => 'EUR ',
            default => $currency . ' ',
        };

        return $symbol . number_format($amount, 2);
    }
}
