<?php

namespace Modules\Sales\Guide;

use App\Support\Guide\ActivityStatusHistory;
use Atomo\Guide\NextAction;
use Atomo\Guide\StepProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Modules\Sales\Models\Remission;
use Modules\Sales\Services\RemissionService;

/** La remision en la guia (F3): imprimir -> entregar; cancelar mientras no se entregue. */
class RemissionSteps extends StepProvider
{
    public function __construct(private RemissionService $remissions) {}

    /** @param  Remission  $document */
    public function label(Model $document): string
    {
        return (string) $document->remission_number;
    }

    /** @param  Remission  $document */
    public function next(Model $document, Authenticatable $user): array
    {
        $folio = ['folio' => $this->label($document)];
        $cancel = NextAction::call('remissions.cancel', 'POST', 'remissions/{id}/cancel', 'sales::guide.remission.cancel', $folio, 'almacen', NextAction::SECONDARY)
            ->blockedBy($this->remissions->cancelProblems($document));

        return match ($document->status) {
            'draft' => [
                NextAction::call('remissions.print', 'POST', 'remissions/{id}/print', 'sales::guide.remission.print', $folio, 'almacen')
                    ->blockedBy($this->remissions->printProblems($document)),
                $cancel,
            ],
            'printed' => [
                NextAction::call('remissions.deliver', 'POST', 'remissions/{id}/deliver', 'sales::guide.remission.deliver', $folio, 'almacen')
                    ->asks(['received_by', 'delivery_notes'])
                    ->blockedBy($this->remissions->deliverProblems($document)),
                $cancel,
            ],
            default => [],
        };
    }

    /** @param  Remission  $document */
    public function department(Model $document): string
    {
        return in_array($document->status, ['draft', 'printed'], true) ? 'almacen' : 'system';
    }

    /** @param  Remission  $document */
    public function origin(Model $document): ?Model
    {
        return $document->sales_order_id ? $document->salesOrder()->first() : null;
    }

    /** @param  Remission  $document */
    public function history(Model $document): array
    {
        return ActivityStatusHistory::for($document);
    }
}
