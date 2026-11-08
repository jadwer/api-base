<?php

namespace Modules\Finance\Guide;

use App\Support\Guide\ActivityStatusHistory;
use Atomo\Guide\StepProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Models\Payment;

/** El cobro en la guia (F3): solo expediente. Nace de la primera cuenta por cobrar a la que se aplico. */
class PaymentSteps extends StepProvider
{
    /** @param  Payment  $document */
    public function label(Model $document): string
    {
        return (string) $document->payment_number;
    }

    public function next(Model $document, Authenticatable $user): array
    {
        return [];
    }

    /** @param  Payment  $document */
    public function department(Model $document): string
    {
        return (float) ($document->unapplied_amount ?? 0) > 0 ? 'cobranza' : 'system';
    }

    /** @param  Payment  $document */
    public function origin(Model $document): ?Model
    {
        $application = $document->paymentApplications()->orderBy('id')->first();

        return $application?->aRInvoice()->first();
    }

    /** @param  Payment  $document */
    public function history(Model $document): array
    {
        return ActivityStatusHistory::for($document);
    }
}
