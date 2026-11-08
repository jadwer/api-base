<?php

namespace Modules\Sales\Guide;

use Atomo\Guide\StepProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Modules\Sales\Models\Backorder;

/** El backorder solo aparece en el expediente (G-1 acotado). */
class BackorderSteps extends StepProvider
{
    /** @param  Backorder  $document */
    public function label(Model $document): string
    {
        return (string) $document->backorder_number;
    }

    public function next(Model $document, Authenticatable $user): array
    {
        return [];
    }

    /** @param  Backorder  $document */
    public function department(Model $document): string
    {
        return in_array($document->status, ['pending', 'partially_fulfilled'], true) ? 'almacen' : 'system';
    }

    /** @param  Backorder  $document */
    public function origin(Model $document): ?Model
    {
        return $document->sales_order_id ? $document->salesOrder()->first() : null;
    }
}
