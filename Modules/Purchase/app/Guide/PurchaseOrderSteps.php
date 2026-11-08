<?php

namespace Modules\Purchase\Guide;

use App\Support\Guide\ActivityStatusHistory;
use Atomo\Guide\StepProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Modules\Purchase\Models\PurchaseOrder;

/**
 * La orden de compra en la guia (F3, G-1 acotado): solo expediente. Cuando
 * nace de una cotizacion (quote_id) se cuelga de su expediente.
 */
class PurchaseOrderSteps extends StepProvider
{
    /** @param  PurchaseOrder  $document */
    public function label(Model $document): string
    {
        return (string) $document->order_number;
    }

    public function next(Model $document, Authenticatable $user): array
    {
        return [];
    }

    /** @param  PurchaseOrder  $document */
    public function department(Model $document): string
    {
        return in_array($document->status, ['pending', 'approved'], true) ? 'compras' : 'system';
    }

    /** @param  PurchaseOrder  $document */
    public function origin(Model $document): ?Model
    {
        return $document->quote_id ? $document->quote()->first() : null;
    }

    /** @param  PurchaseOrder  $document */
    public function history(Model $document): array
    {
        return ActivityStatusHistory::for($document);
    }
}
