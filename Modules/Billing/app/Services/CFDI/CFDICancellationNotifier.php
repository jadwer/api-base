<?php

namespace Modules\Billing\Services\CFDI;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Billing\Mail\CFDICancellationMail;
use Modules\Billing\Models\CFDIInvoice;
use Modules\MailerManager\Models\SystemEmail;

/**
 * Avisos de cancelacion de CFDI (estilo Bind): al solicitarla y al
 * resolverse. Van al correo de la empresa emisora y al del receptor, que
 * debe aceptar o rechazar la cancelacion en el buzon del SAT.
 */
class CFDICancellationNotifier
{
    public const REGISTRY_KEY = 'billing.cfdi_cancellation';

    public function requested(CFDIInvoice $invoice): void
    {
        $this->send($invoice, CFDICancellationMail::STAGE_REQUESTED);
    }

    public function resolved(CFDIInvoice $invoice): void
    {
        $this->send(
            $invoice,
            $invoice->isCancelled() ? CFDICancellationMail::STAGE_CANCELLED : CFDICancellationMail::STAGE_REJECTED
        );
    }

    /** @return list<string> */
    public function recipients(CFDIInvoice $invoice): array
    {
        $invoice->loadMissing(['companySetting', 'contact']);

        return collect([$invoice->companySetting?->email, $invoice->contact?->email])
            ->filter(fn ($email) => is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL))
            ->map(fn (string $email) => strtolower(trim($email)))
            ->unique()
            ->values()
            ->all();
    }

    protected function send(CFDIInvoice $invoice, string $stage): void
    {
        if (!SystemEmail::isEnabled(self::REGISTRY_KEY)) {
            return;
        }

        $recipients = $this->recipients($invoice);
        if ($recipients === []) {
            Log::warning('CFDI cancellation notice without recipients', ['invoice_id' => $invoice->id]);
            return;
        }

        foreach ($recipients as $email) {
            Mail::to($email)->send(new CFDICancellationMail($invoice, $stage));
        }
    }
}
