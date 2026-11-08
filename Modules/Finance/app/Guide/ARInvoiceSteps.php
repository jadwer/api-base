<?php

namespace Modules\Finance\Guide;

use App\Support\Guide\ActivityStatusHistory;
use Atomo\Guide\StepProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Models\ARInvoice;
use Modules\Finance\Models\PaymentApplication;

/**
 * La cuenta por cobrar en la guia (F3, G-1 acotado): solo expediente (folio,
 * estado, departamento, historial, cobros ligados). Sin acciones: cobrar y
 * timbrar siguen en sus pantallas.
 */
class ARInvoiceSteps extends StepProvider
{
    /** @param  ARInvoice  $document */
    public function label(Model $document): string
    {
        return (string) $document->invoice_number;
    }

    public function next(Model $document, Authenticatable $user): array
    {
        return [];
    }

    /** @param  ARInvoice  $document */
    public function department(Model $document): string
    {
        return in_array($document->status, ['pending', 'partial', 'overdue'], true) ? 'cobranza' : 'system';
    }

    /** @param  ARInvoice  $document */
    public function origin(Model $document): ?Model
    {
        return $document->sales_order_id ? $document->salesOrder()->first() : null;
    }

    /** @param  ARInvoice  $document */
    public function links(Model $document): iterable
    {
        return $document->paymentApplications()->with('payment')->orderBy('id')->get()
            ->map(fn (PaymentApplication $application) => $application->payment)
            ->filter()
            ->unique('id')
            ->values();
    }

    /** @param  ARInvoice  $document */
    public function history(Model $document): array
    {
        return ActivityStatusHistory::for($document);
    }
}
