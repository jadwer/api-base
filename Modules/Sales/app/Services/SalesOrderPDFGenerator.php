<?php

namespace Modules\Sales\Services;

use Modules\Sales\Models\SalesOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * Generate PDF receipts for Sales Orders
 *
 * Creates simple PDF order receipts for customers.
 * For fiscal invoices (CFDI), use the Billing module.
 */
class SalesOrderPDFGenerator
{
    /**
     * Generate PDF for sales order
     *
     * @param SalesOrder $order
     * @return string Path to generated PDF
     */
    public function generate(SalesOrder $order): string
    {
        $data = [
            'order' => $order,
            'items' => $order->items()->with('product')->get(),
            'totals' => $this->calculateTotals($order),
            'customer' => $order->customer,
            'shippingAddress' => $order->shipping_address,
        ];

        $pdf = Pdf::loadView('sales::order-pdf', $data)
            ->setPaper('letter')
            ->setOption('defaultFont', 'DejaVu Sans');

        $filename = $this->generateFilename($order);
        $path = "orders/{$order->id}/{$filename}";

        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }

    /**
     * Calculate order totals
     *
     * @param SalesOrder $order
     * @return array
     */
    protected function calculateTotals(SalesOrder $order): array
    {
        $subtotal = $order->subtotal_amount ?? $order->total_amount;
        $tax = $order->tax_amount ?? 0;
        $shipping = $order->shipping_amount ?? 0;
        $discount = $order->discount_amount ?? 0;

        return [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping' => $shipping,
            'discount' => $discount,
            'total' => $order->total_amount,
        ];
    }

    /**
     * Generate filename for PDF
     *
     * @param SalesOrder $order
     * @return string
     */
    protected function generateFilename(SalesOrder $order): string
    {
        return "order_{$order->order_number}.pdf";
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
     * Get existing PDF path or generate new one
     *
     * @param SalesOrder $order
     * @return string
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
     * Format amount for display
     *
     * @param float $amount
     * @param string $currency
     * @return string
     */
    public function formatAmount(float $amount, string $currency = 'MXN'): string
    {
        $symbol = match($currency) {
            'MXN' => '$',
            'USD' => 'USD $',
            'EUR' => '€',
            default => $currency . ' ',
        };

        return $symbol . number_format($amount, 2);
    }
}
