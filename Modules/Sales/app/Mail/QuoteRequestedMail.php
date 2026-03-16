<?php

namespace Modules\Sales\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\MailerManager\Traits\UsesEmailTemplate;
use Modules\Sales\Models\Quote;

class QuoteRequestedMail extends Mailable
{
    use Queueable, SerializesModels, UsesEmailTemplate;

    public Quote $quote;
    public array $quoteSummary;
    public bool $isAdmin;

    protected function getRegistryKey(): string
    {
        return $this->isAdmin
            ? 'sales.quote_requested.admin'
            : 'sales.quote_requested.customer';
    }

    public function __construct(Quote $quote, bool $isAdmin = false)
    {
        $this->quote = $quote->load(['items.product', 'contact']);
        $this->isAdmin = $isAdmin;
        $this->quoteSummary = $this->getQuoteSummary();
    }

    public function build()
    {
        $templateMail = $this->buildWithTemplate();
        if ($templateMail) {
            return $templateMail;
        }

        $subject = $this->isAdmin
            ? 'Nueva solicitud de cotizacion - ' . $this->quote->quote_number
            : 'Su solicitud de cotizacion ha sido recibida - ' . $this->quote->quote_number;

        return $this->subject($subject)
            ->view('sales::emails.quote-requested')
            ->with([
                'quote' => $this->quote,
                'quoteSummary' => $this->quoteSummary,
                'isAdmin' => $this->isAdmin,
            ]);
    }

    protected function getTemplateVariables(): array
    {
        $items = collect($this->quoteSummary['items'])->map(function ($item) {
            return $item['name'] . ' (x' . $item['quantity'] . ') - $' . number_format($item['subtotal'], 2);
        })->implode("\n");

        return [
            'quote_number' => $this->quoteSummary['quote_number'],
            'customer_name' => $this->quoteSummary['customer_name'],
            'customer_email' => $this->quoteSummary['customer_email'] ?? '',
            'quote_date' => $this->quoteSummary['quote_date'] ?? '',
            'items' => $items,
            'total' => number_format($this->quoteSummary['total'] ?? 0, 2),
            'currency' => $this->quoteSummary['currency'] ?? 'MXN',
            'company_name' => config('app.name', 'Labor Wasser de Mexico'),
        ];
    }

    protected function getQuoteSummary(): array
    {
        return [
            'quote_number' => $this->quote->quote_number,
            'quote_date' => $this->quote->quote_date?->format('d/m/Y'),
            'customer_name' => $this->quote->contact?->name ?? 'Cliente',
            'customer_email' => $this->quote->contact?->email,
            'items' => $this->quote->items->map(function ($item) {
                return [
                    'name' => $item->product_name ?? $item->product?->name ?? 'Producto',
                    'sku' => $item->product_sku ?? $item->product?->sku ?? '-',
                    'quantity' => $item->quantity,
                    'unit_price' => $item->quoted_price,
                    'subtotal' => $item->total,
                ];
            })->toArray(),
            'total' => $this->quote->total_amount,
            'currency' => $this->quote->currency ?? 'MXN',
        ];
    }
}
