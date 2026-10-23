<?php

namespace Modules\Sales\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\MailerManager\Traits\UsesEmailTemplate;
use Modules\Sales\Mail\Concerns\SummarizesQuoteItems;
use Modules\Sales\Models\Quote;

class QuoteSentMail extends Mailable
{
    use Queueable, SerializesModels, UsesEmailTemplate, SummarizesQuoteItems;

    public Quote $quote;
    public array $quoteSummary;
    protected ?string $pdfPath;

    protected function getRegistryKey(): string
    {
        return 'sales.quote_sent';
    }

    public function __construct(Quote $quote, ?string $pdfPath = null)
    {
        $this->quote = $quote->load(['items.product', 'contact']);
        $this->pdfPath = $pdfPath;
        $this->quoteSummary = $this->getQuoteSummary();
        $this->quoteSummary['price_pending_notice'] = $this->pendingPriceNotice($this->quoteSummary['items']);
    }

    public function build()
    {
        $templateMail = $this->buildWithTemplate();
        if ($templateMail) {
            if ($this->pdfPath && file_exists($this->pdfPath)) {
                $templateMail->attach($this->pdfPath, [
                    'as' => 'Cotizacion-' . $this->quote->quote_number . '.pdf',
                    'mime' => 'application/pdf',
                ]);
            }

            return $templateMail;
        }

        $mail = $this->subject('Cotizacion ' . $this->quote->quote_number . ' - ' . app(\Modules\AppConfig\Services\AppSettingResolver::class)->get('company.name', config('app.name', 'Demo Company')))
            ->view('sales::emails.quote-sent')
            ->with([
                'quote' => $this->quote,
                'quoteSummary' => $this->quoteSummary,
            ]);

        if ($this->pdfPath && file_exists($this->pdfPath)) {
            $mail->attach($this->pdfPath, [
                'as' => 'Cotizacion-' . $this->quote->quote_number . '.pdf',
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
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
            'valid_until' => $this->quoteSummary['valid_until'] ?? '',
            'subtotal' => number_format($this->quoteSummary['subtotal'] ?? 0, 2),
            'tax' => number_format($this->quoteSummary['tax'] ?? 0, 2),
            'total' => number_format($this->quoteSummary['total'] ?? 0, 2),
            'currency' => $this->quoteSummary['currency'] ?? 'MXN',
            // La plantilla vigente ya imprime {{#if notes}}: la leyenda de
            // precio pendiente viaja ahi para que aparezca sin re-sembrar.
            'notes' => trim($notice . "\n" . ($this->quoteSummary['notes'] ?? '')),
            'items' => $items,
            'price_pending_notice' => $notice,
            'company_name' => app(\Modules\AppConfig\Services\AppSettingResolver::class)->get('company.name', config('app.name', 'Demo Company')),
        ];
    }

    protected function getQuoteSummary(): array
    {
        return [
            'quote_number' => $this->quote->quote_number,
            'quote_date' => $this->quote->quote_date?->format('d/m/Y'),
            'valid_until' => $this->quote->valid_until?->format('d/m/Y'),
            'customer_name' => $this->quote->contact?->name ?: 'Cliente',
            'customer_email' => $this->quote->contact?->email,
            'items' => $this->quoteItemsSummary($this->quote),
            'subtotal' => $this->quote->subtotal_amount,
            'discount' => $this->quote->discount_amount,
            'tax' => $this->quote->tax_amount,
            'total' => $this->quote->total_amount,
            'currency' => $this->quote->currency ?? 'MXN',
            'notes' => $this->quote->notes,
        ];
    }
}
