<?php

namespace Modules\Sales\Guide;

use App\Support\Guide\ActivityStatusHistory;
use Atomo\Guide\NextAction;
use Atomo\Guide\StepProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Sales\Models\Quote;
use Modules\Sales\Services\QuoteWorkflowService;

/**
 * La cotizacion en la guia (flujo guiado F3). Las reglas salen de
 * QuoteWorkflowService (las mismas que usan los endpoints); aqui solo se
 * decide que ofrecer en cada estado y con que frase.
 *
 * Caso Jasim 2 (2026-09-10): el vendedor acepta desde borrador cuando el
 * cliente confirma por telefono o mostrador; enviar por correo no es
 * requisito. Granel (D8): con partidas a precio por confirmar, lo primero es
 * capturar el precio (la frase "tienes que llamar" de la junta del 30-10).
 */
class QuoteSteps extends StepProvider
{
    public function __construct(private QuoteWorkflowService $workflow) {}

    /** @param  Quote  $document */
    public function label(Model $document): string
    {
        return (string) $document->quote_number;
    }

    /** @param  Quote  $document */
    public function next(Model $document, Authenticatable $user): array
    {
        $folio = ['folio' => $this->label($document)];
        $asStaff = $user->can('quotes.update');
        $cancel = NextAction::call('quotes.cancel', 'POST', 'quotes/{id}/cancel', 'sales::guide.quote.cancel', $folio, kind: NextAction::SECONDARY)
            ->blockedBy($this->workflow->cancelProblems($document));

        $actions = [];
        $pendingPrice = $this->workflow->pendingPriceCount($document);
        if ($pendingPrice > 0 && in_array($document->status, ['draft', 'sent', 'accepted'], true)) {
            // Capturar precio es editar partidas: la pantalla abre su editor.
            $actions[] = NextAction::call('quotes.update', 'PATCH', 'quotes/{id}', 'sales::guide.quote.confirm_price', $folio + ['count' => $pendingPrice])
                ->asks(['items']);
        }

        switch ($document->status) {
            case 'draft':
                $actions[] = NextAction::call('quotes.send', 'POST', 'quotes/{id}/send', 'sales::guide.quote.send', $folio, kind: $pendingPrice > 0 ? NextAction::SECONDARY : NextAction::PRIMARY)
                    ->asks(['recipients'])
                    ->blockedBy($this->workflow->sendProblems($document));
                $actions[] = NextAction::call('quotes.accept', 'POST', 'quotes/{id}/accept', 'sales::guide.quote.accept_direct', $folio, kind: NextAction::SECONDARY)
                    ->blockedBy($this->workflow->acceptProblems($document, $asStaff));
                $actions[] = $cancel;
                break;
            case 'sent':
                $actions[] = NextAction::call('quotes.accept', 'POST', 'quotes/{id}/accept', 'sales::guide.quote.accept', $folio, kind: $pendingPrice > 0 ? NextAction::SECONDARY : NextAction::PRIMARY)
                    ->blockedBy($this->workflow->acceptProblems($document, $asStaff));
                $actions[] = NextAction::call('quotes.reject', 'POST', 'quotes/{id}/reject', 'sales::guide.quote.reject', $folio, kind: NextAction::SECONDARY)
                    ->asks(['reason'])
                    ->withReason();
                $actions[] = $cancel;
                break;
            case 'accepted':
                $actions[] = NextAction::call('quotes.convert', 'POST', 'quotes/{id}/convert', 'sales::guide.quote.convert', $folio, kind: $pendingPrice > 0 ? NextAction::SECONDARY : NextAction::PRIMARY)
                    ->asks(['order_type', 'acceptance_channel', 'customer_po_number', 'payment_method', 'credit_days'])
                    ->blockedBy($this->workflow->convertProblems($document));
                $actions[] = $cancel;
                break;
            case 'expired':
                $actions[] = NextAction::call('quotes.duplicate', 'POST', 'quotes/{id}/duplicate', 'sales::guide.quote.duplicate', $folio);
                break;
        }

        return $actions;
    }

    /** @param  Quote  $document */
    public function department(Model $document): string
    {
        return in_array($document->status, ['converted', 'rejected', 'cancelled', 'expired'], true) ? 'system' : 'ventas';
    }

    /** @param  Quote  $document */
    public function links(Model $document): iterable
    {
        $links = [];
        if ($document->sales_order_id && ($order = $document->salesOrder()->first())) {
            $links[] = $order;
        }
        // La orden de compra generada desde la cotizacion (purchase_orders.quote_id
        // o quotes.purchase_order_id, segun la version que la creo).
        $pos = PurchaseOrder::query()
            ->where(fn ($q) => $q->where('quote_id', $document->getKey())
                ->when($document->purchase_order_id, fn ($q, $poId) => $q->orWhere('id', $poId)))
            ->orderBy('id')
            ->get();
        foreach ($pos as $po) {
            $links[] = $po;
        }

        return $links;
    }

    /**
     * Activity log (registra `status`), completado con las fechas propias de
     * la cotizacion cuando el log no las trae (datos anteriores al log).
     *
     * @param  Quote  $document
     */
    public function history(Model $document): array
    {
        $fromDates = [];
        $fromDates[] = ['from' => null, 'to' => 'draft', 'at' => $document->created_at->toIso8601String(), 'by' => null, 'reason' => null];
        foreach (['sent_at' => 'sent', 'accepted_at' => 'accepted', 'rejected_at' => 'rejected', 'converted_at' => 'converted'] as $column => $status) {
            if ($document->{$column}) {
                $fromDates[] = ['from' => null, 'to' => $status, 'at' => $document->{$column}->toIso8601String(), 'by' => null, 'reason' => null];
            }
        }

        return ActivityStatusHistory::merge(ActivityStatusHistory::for($document), $fromDates);
    }
}
