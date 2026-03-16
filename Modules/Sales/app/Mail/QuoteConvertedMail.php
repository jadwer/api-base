<?php

namespace Modules\Sales\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\MailerManager\Traits\UsesEmailTemplate;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\SalesOrder;

class QuoteConvertedMail extends Mailable
{
    use Queueable, SerializesModels, UsesEmailTemplate;

    public Quote $quote;
    public SalesOrder $order;
    public array $quoteSummary;
    public bool $isAdmin;

    protected function getRegistryKey(): string
    {
        return $this->isAdmin
            ? 'sales.quote_converted.admin'
            : 'sales.quote_converted.customer';
    }

    /**
     * Create a new message instance.
     */
    public function __construct(Quote $quote, SalesOrder $order, bool $isAdmin = false)
    {
        $this->quote = $quote->load(['items.product', 'contact']);
        $this->order = $order->load(['items.product']);
        $this->isAdmin = $isAdmin;

        $this->quoteSummary = $this->getQuoteSummary();
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $templateMail = $this->buildWithTemplate();
        if ($templateMail) {
            return $templateMail;
        }

        $subject = $this->isAdmin
            ? 'Cotización convertida a pedido - ' . $this->quote->quote_number
            : 'Su cotización ha sido confirmada - ' . $this->quote->quote_number;

        return $this->subject($subject)
            ->view('sales::emails.quote-converted')
            ->with([
                'quote' => $this->quote,
                'order' => $this->order,
                'quoteSummary' => $this->quoteSummary,
                'isAdmin' => $this->isAdmin,
            ]);
    }

    protected function getTemplateVariables(): array
    {
        return [
            'quote_number' => $this->quoteSummary['quote_number'],
            'order_number' => $this->quoteSummary['order_number'],
            'customer_name' => $this->quoteSummary['customer_name'],
            'total' => number_format($this->quoteSummary['total'] ?? 0, 2),
            'currency' => $this->quoteSummary['currency'] ?? 'MXN',
            'company_name' => config('app.name', 'Labor Wasser de Mexico'),
        ];
    }

    /**
     * Get quote summary for email
     */
    protected function getQuoteSummary(): array
    {
        return [
            'quote_number' => $this->quote->quote_number,
            'quote_date' => $this->quote->quote_date?->format('d/m/Y'),
            'order_number' => $this->order->order_number,
            'order_date' => $this->order->order_date?->format('d/m/Y'),
            'customer_name' => $this->quote->contact?->name ?? 'Cliente',
            'customer_email' => $this->quote->contact?->email,
            'items' => $this->quote->items->map(function ($item) {
                return [
                    'name' => $item->product_name ?? $item->product?->name ?? 'Producto',
                    'sku' => $item->product_sku ?? $item->product?->sku ?? '-',
                    'quantity' => $item->quantity,
                    'unit_price' => $item->quoted_price,
                    'subtotal' => $item->total,
                    'notes' => $item->notes,
                ];
            })->toArray(),
            'subtotal' => $this->quote->subtotal_amount,
            'discount' => $this->quote->discount_amount,
            'tax' => $this->quote->tax_amount,
            'total' => $this->quote->total_amount,
            'currency' => $this->quote->currency ?? 'MXN',
            'notes' => $this->quote->notes,
            'estimated_eta' => $this->quote->estimated_eta,
            'shipping_address' => $this->quote->shipping_address,
        ];
    }
}
