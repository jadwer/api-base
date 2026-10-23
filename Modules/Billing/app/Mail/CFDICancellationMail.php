<?php

namespace Modules\Billing\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Services\CFDI\CFDICancellationNotifier;
use Modules\MailerManager\Traits\UsesEmailTemplate;

class CFDICancellationMail extends Mailable
{
    use Queueable, SerializesModels, UsesEmailTemplate;

    public const STAGE_REQUESTED = 'requested';
    public const STAGE_CANCELLED = 'cancelled';
    public const STAGE_REJECTED = 'rejected';

    /** Motivos de cancelacion del SAT. */
    public const MOTIVOS = [
        '01' => '01 - Comprobante emitido con errores con relación',
        '02' => '02 - Comprobante emitido con errores sin relación',
        '03' => '03 - No se llevó a cabo la operación',
        '04' => '04 - Operación nominativa relacionada en una factura global',
    ];

    public function __construct(public CFDIInvoice $invoice, public string $stage)
    {
    }

    protected function getRegistryKey(): string
    {
        return CFDICancellationNotifier::REGISTRY_KEY;
    }

    public function headline(): string
    {
        return match ($this->stage) {
            self::STAGE_CANCELLED => 'La factura fue cancelada',
            self::STAGE_REJECTED => 'La cancelación de la factura fue rechazada',
            default => 'Se está cancelando esta factura',
        };
    }

    public function build()
    {
        $templateMail = $this->buildWithTemplate();
        if ($templateMail) {
            return $templateMail;
        }

        return $this->subject($this->headline() . ' ' . $this->invoice->getFolioCompleto())
            ->view('billing::emails.cfdi-cancellation')
            ->with([
                'headline' => $this->headline(),
                'stage' => $this->stage,
                'rows' => $this->rows(),
            ]);
    }

    /** @return array<string, string> */
    public function rows(): array
    {
        $invoice = $this->invoice;

        return [
            'Folio' => $invoice->getFolioCompleto(),
            'Folio fiscal (UUID)' => (string) $invoice->uuid,
            'Receptor' => trim(($invoice->receptor_nombre ?? '') . ' (' . ($invoice->receptor_rfc ?? '') . ')'),
            'Total' => '$' . number_format($invoice->total / 100, 2) . ' ' . ($invoice->moneda ?? 'MXN'),
            'Motivo' => self::MOTIVOS[$invoice->motivo_cancelacion] ?? (string) $invoice->motivo_cancelacion,
            'Estatus' => (string) ($invoice->cancellation_status ?? ''),
        ];
    }

    protected function getTemplateVariables(): array
    {
        $rows = $this->rows();

        return [
            'headline' => $this->headline(),
            'invoice_folio' => $rows['Folio'],
            'invoice_uuid' => $rows['Folio fiscal (UUID)'],
            'customer_name' => (string) $this->invoice->receptor_nombre,
            'customer_rfc' => (string) $this->invoice->receptor_rfc,
            'total' => $rows['Total'],
            'cancellation_reason' => $rows['Motivo'],
            'cancellation_status' => $rows['Estatus'],
            'company_name' => app(\Modules\AppConfig\Services\AppSettingResolver::class)->get('company.name', config('app.name')),
        ];
    }
}
