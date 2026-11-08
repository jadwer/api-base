<?php

namespace Modules\Sales\Guide;

use App\Support\Guide\ActivityStatusHistory;
use Atomo\Guide\NextAction;
use Atomo\Guide\StepProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Modules\Billing\Models\CFDIInvoice;
use Modules\Finance\Services\ARInvoiceService;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Services\OrderStatusService;
use Modules\Sales\Services\RemissionService;

/**
 * El pedido en la guia (F3). Camino feliz: confirmar -> remisionar (almacen)
 * -> entregar -> facturar. Las reglas salen de OrderStatusService,
 * RemissionService y ARInvoiceService (las mismas que los endpoints).
 */
class SalesOrderSteps extends StepProvider
{
    public const OPEN = ['draft', 'pending', 'confirmed', 'processing', 'shipped'];

    public function __construct(
        private OrderStatusService $status,
        private RemissionService $remissions,
        private ARInvoiceService $invoices,
    ) {}

    /** @param  SalesOrder  $document */
    public function label(Model $document): string
    {
        return (string) $document->order_number;
    }

    /** @param  SalesOrder  $document */
    public function next(Model $document, Authenticatable $user): array
    {
        $folio = ['folio' => $this->label($document)];
        $cancel = NextAction::call('sales-orders.cancel', 'POST', 'sales-orders/{id}/cancel', 'sales::guide.order.cancel', $folio, kind: NextAction::SECONDARY)
            ->blockedBy($this->status->cancelProblems($document));

        switch ($document->status) {
            case 'draft':
            case 'pending':
                return [
                    NextAction::call('sales-orders.confirm', 'POST', 'orders/{id}/status', 'sales::guide.order.confirm', $folio, body: ['status' => 'confirmed'])
                        ->blockedBy($this->status->confirmProblems($document)),
                    $cancel,
                ];
            case 'confirmed':
            case 'processing':
            case 'shipped':
                $remit = $this->remissions->remitProblems($document);
                // Sin partidas por surtir, el paso que sigue es cerrar la entrega.
                $allRemitted = isset($remit['pending']);

                return [
                    NextAction::call('sales-orders.remit', 'POST', 'remissions/from-order-full', 'sales::guide.order.remit', $folio, 'almacen', $allRemitted ? NextAction::SECONDARY : NextAction::PRIMARY, ['sales_order_id' => $document->getKey()])
                        ->asks(['items', 'warehouse_id', 'delivery_notes'])
                        ->blockedBy($remit),
                    NextAction::call('sales-orders.deliver', 'POST', 'orders/{id}/status', 'sales::guide.order.deliver', $folio, 'almacen', $allRemitted ? NextAction::PRIMARY : NextAction::SECONDARY, ['status' => 'delivered'])
                        ->blockedBy($this->status->deliverProblems($document)),
                    $cancel,
                ];
            case 'delivered':
            case 'completed':
                if ($this->hasCfdi($document)) {
                    return [];
                }

                return [
                    NextAction::call('sales-orders.invoice', 'POST', 'sales-orders/{id}/facturar', 'sales::guide.order.invoice', $folio, 'facturacion')
                        ->asks(['forma_pago'])
                        ->blockedBy($this->invoices->invoiceProblems($document)),
                ];
        }

        return [];
    }

    /** @param  SalesOrder  $document */
    public function department(Model $document): string
    {
        return match ($document->status) {
            'draft', 'pending', 'confirmed' => 'ventas',
            'processing', 'shipped' => 'almacen',
            'delivered', 'completed' => $this->hasCfdi($document) ? 'system' : 'facturacion',
            default => 'system',
        };
    }

    /** @param  SalesOrder  $document */
    public function origin(Model $document): ?Model
    {
        return $document->quote_id ? $document->quote()->first() : null;
    }

    /** @param  SalesOrder  $document */
    public function links(Model $document): iterable
    {
        return [
            ...$document->remissions()->orderBy('id')->get(),
            ...$document->shipments()->orderBy('id')->get(),
            ...$document->backorders()->orderBy('id')->get(),
            ...$document->arInvoices()->orderBy('id')->get(),
        ];
    }

    /**
     * D8: `metadata.status_history` (lo escribe OrderStatusService) unido con el
     * activity log, que ademas registra los cambios hechos por otros caminos.
     *
     * @param  SalesOrder  $document
     */
    public function history(Model $document): array
    {
        $own = [];
        foreach ((array) ($document->metadata['status_history'] ?? []) as $step) {
            if (! is_array($step) || ! isset($step['to'])) {
                continue;
            }
            $own[] = [
                'from' => isset($step['from']) ? (string) $step['from'] : null,
                'to' => (string) $step['to'],
                'at' => isset($step['changed_at']) ? (string) $step['changed_at'] : null,
                'by' => ActivityStatusHistory::userName($step['changed_by'] ?? null),
                'reason' => isset($step['notes']) && $step['notes'] !== '' ? (string) $step['notes'] : null,
            ];
        }

        return ActivityStatusHistory::merge($own, ActivityStatusHistory::for($document));
    }

    /** Ya hay CFDI vigente para el pedido (misma regla que esconde "Generar factura" en pantalla). */
    private function hasCfdi(SalesOrder $order): bool
    {
        return CFDIInvoice::query()
            ->whereIn('ar_invoice_id', $order->arInvoices()->select('id'))
            ->where('status', '!=', 'cancelled')
            ->exists();
    }
}
