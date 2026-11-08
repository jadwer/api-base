<?php

namespace Modules\Sales\Guide;

use Atomo\Guide\StepProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Modules\Sales\Models\Shipment;

/** El envio solo aparece en el expediente (G-1 acotado): folio, estado y de que pedido nace. */
class ShipmentSteps extends StepProvider
{
    /** @param  Shipment  $document */
    public function label(Model $document): string
    {
        return (string) $document->shipment_number;
    }

    public function next(Model $document, Authenticatable $user): array
    {
        return [];
    }

    /** @param  Shipment  $document */
    public function department(Model $document): string
    {
        return in_array($document->status, ['pending', 'processing', 'shipped'], true) ? 'almacen' : 'system';
    }

    /** @param  Shipment  $document */
    public function origin(Model $document): ?Model
    {
        return $document->sales_order_id ? $document->salesOrder()->first() : null;
    }
}
