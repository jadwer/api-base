<?php

namespace Modules\Sales\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\MailerManager\Traits\UsesEmailTemplate;
use Modules\Sales\Mail\Concerns\SummarizesQuoteItems;
use Modules\Sales\Models\Quote;

class QuoteRequestedMail extends Mailable
{
    use Queueable, SerializesModels, UsesEmailTemplate, SummarizesQuoteItems;

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
        $this->quoteSummary['price_pending_notice'] = $this->pendingPriceNotice($this->quoteSummary['items']);
    }

    public function build()
    {
        $templateMail = $this->buildWithTemplate();
        if ($templateMail) {
            return $templateMail;
        }

        $subject = $this->isAdmin
            ? 'Nueva solicitud de cotización - ' . $this->quote->quote_number
            : 'Su solicitud de cotización ha sido recibida - ' . $this->quote->quote_number;

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
        $items = $this->templateItems($this->quoteSummary['items']);
        $notice = $this->pendingPriceNotice($this->quoteSummary['items']);

        return [
            'quote_number' => $this->quoteSummary['quote_number'],
            'customer_name' => $this->quoteSummary['customer_name'],
            'customer_email' => $this->quoteSummary['customer_email'] ?? '',
            'quote_date' => $this->quoteSummary['quote_date'] ?? '',
            'items' => $items,
            'price_pending_notice' => $notice,
            'total' => number_format($this->quoteSummary['total'] ?? 0, 2),
            'currency' => $this->quoteSummary['currency'] ?? 'MXN',
            'company_name' => app(\Modules\AppConfig\Services\AppSettingResolver::class)->get('company.name', config('app.name', 'Tu Empresa')),
        ];
    }

    protected function getQuoteSummary(): array
    {
        return [
            'quote_number' => $this->quote->quote_number,
            'quote_date' => $this->quote->quote_date?->format('d/m/Y'),
            'customer_name' => $this->quote->contact?->name ?: 'Cliente',
            'customer_email' => $this->quote->contact?->email,
            'items' => $this->quoteItemsSummary($this->quote),
            'total' => $this->quote->total_amount,
            'currency' => $this->quote->currency ?? 'MXN',
        ];
    }
}
