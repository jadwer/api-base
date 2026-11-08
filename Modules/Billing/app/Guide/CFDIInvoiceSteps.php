<?php

namespace Modules\Billing\Guide;

use App\Support\Guide\ActivityStatusHistory;
use Atomo\Guide\StepProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Modules\Billing\Models\CFDIInvoice;

/**
 * El CFDI en la guia (F3, G-1 acotado): solo expediente. Nace de la cuenta
 * por cobrar (ingreso) o del cobro (complemento de pago, REP).
 */
class CFDIInvoiceSteps extends StepProvider
{
    /** @param  CFDIInvoice  $document */
    public function label(Model $document): string
    {
        $folio = trim(($document->series ?? '').'-'.($document->folio ?? ''), '-');

        return $folio !== '' ? $folio : (string) ($document->uuid ?? $document->getKey());
    }

    public function next(Model $document, Authenticatable $user): array
    {
        return [];
    }

    /** @param  CFDIInvoice  $document */
    public function department(Model $document): string
    {
        return in_array($document->status, ['draft', 'error', CFDIInvoice::STATUS_CANCEL_PENDING], true) ? 'facturacion' : 'system';
    }

    /** @param  CFDIInvoice  $document */
    public function origin(Model $document): ?Model
    {
        if ($document->ar_invoice_id) {
            return $document->arInvoice()->first();
        }

        return $document->ar_payment_id ? $document->arPayment()->first() : null;
    }

    /** @param  CFDIInvoice  $document */
    public function history(Model $document): array
    {
        return ActivityStatusHistory::for($document);
    }
}
