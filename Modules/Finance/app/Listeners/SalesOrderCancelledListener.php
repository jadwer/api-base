<?php

namespace Modules\Finance\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Finance\Models\ARInvoice;
use Modules\Accounting\Services\AccountingService;
use Modules\Sales\Events\SalesOrderCancelled;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Services\InventoryMovementService;

class SalesOrderCancelledListener
{
    public function __construct(
        private AccountingService $accountingService,
        private InventoryMovementService $inventoryMovementService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(SalesOrderCancelled $event): void
    {
        $salesOrder = $event->salesOrder;

        Log::info('SalesOrderCancelledListener: Starting', [
            'sales_order_id' => $salesOrder->id,
            'order_number' => $salesOrder->order_number
        ]);

        // Refactor ciclo (re-auditoria N4): usar DB::transaction(closure) en vez de
        // beginTransaction/commit/rollBack MANUAL. Este listener corre SINCRONO dentro del
        // DB::transaction de OrderStatusService::updateStatus; el patron manual anidado era
        // fragil (el commit del savepoint no es un commit real y podia dejar estados
        // inconsistentes). DB::transaction maneja el savepoint anidado correctamente y
        // revierte + propaga la excepcion de forma limpia.
        DB::transaction(function () use ($salesOrder) {
            // Find associated AR Invoice
            $arInvoice = ARInvoice::where('sales_order_id', $salesOrder->id)->first();

            if (!$arInvoice) {
                Log::info('SalesOrderCancelledListener: No AR Invoice found to cancel');
                // Aun sin factura, hay que reponer el stock entregado y marcar la orden.
                $this->reverseDeliveredStock($salesOrder);
                $salesOrder->update([
                    // Refactor ciclo (5c/E2E): invoicing_status es enum(not_invoiced,partial,
                    // invoiced) y NO acepta 'cancelled' (truncaba la escritura y hacia fallar
                    // TODO el listener). La factura fue anulada -> la orden queda sin factura
                    // vigente = not_invoiced. financial_status es varchar y si admite cancelled.
                    'invoicing_status' => 'not_invoiced',
                    'financial_status' => 'cancelled',
                ]);
                return;
            }

            // Handle based on invoice status
            if ($arInvoice->status === 'draft') {
                $arInvoice->update(['status' => 'voided']);

                Log::info('SalesOrderCancelledListener: AR Invoice voided (draft)', [
                    'ar_invoice_id' => $arInvoice->id
                ]);
            } elseif (in_array($arInvoice->status, ['posted', 'partial', 'paid'])) {
                // Refactor ciclo (Patron 2, P1): antes 'paid' caia al else y no se anulaba
                // ni se revertia -> ingresos/AR inflados al cancelar una venta ya cobrada.
                // Ahora se anula la factura y se revierte su asiento tambien para 'paid'.
                // OJO: si la factura tenia cobros aplicados (partial/paid), el DINERO ya
                // entro; la reversa del asiento de la FACTURA no devuelve el cobro. Ese
                // saldo a favor del cliente queda marcado para tratamiento manual (nota de
                // credito / devolucion). NO se borran PaymentApplication en automatico.
                $hadPayments = in_array($arInvoice->status, ['partial', 'paid'])
                    || (float) ($arInvoice->paid_amount ?? 0) > 0;

                $arInvoice->update(['status' => 'voided']);

                // Reverse the GL entry if it exists
                if ($arInvoice->journal_entry_id) {
                    $journalEntry = $arInvoice->journalEntry;
                    if ($journalEntry && $journalEntry->status === 'posted') {
                        $this->accountingService->reverseJournalEntry(
                            $journalEntry,
                            "Sales Order #{$salesOrder->order_number} cancelled"
                        );
                    }
                }

                if ($hadPayments) {
                    // Dejar rastro accionable: hay un cobro que requiere nota de credito
                    // o devolucion. No se automatiza el reembolso.
                    $arInvoice->update([
                        'notes' => trim(($arInvoice->notes ?? '') . ' | CANCELADA con cobro aplicado ('
                            . ($arInvoice->paid_amount ?? 0) . '): requiere nota de credito/devolucion.'),
                    ]);
                    Log::warning('SalesOrderCancelledListener: factura CANCELADA con cobro aplicado, requiere nota de credito/devolucion', [
                        'ar_invoice_id' => $arInvoice->id,
                        'paid_amount' => $arInvoice->paid_amount ?? 0,
                    ]);
                }

                Log::info('SalesOrderCancelledListener: AR Invoice voided + GL reversed', [
                    'ar_invoice_id' => $arInvoice->id,
                    'journal_entry_id' => $arInvoice->journal_entry_id,
                    'had_payments' => $hadPayments,
                ]);
            } else {
                Log::warning('SalesOrderCancelledListener: AR Invoice cannot be voided', [
                    'ar_invoice_id' => $arInvoice->id,
                    'status' => $arInvoice->status
                ]);
            }

            // Refactor ciclo (5b/F2): reponer el stock que SALIO por las entregas.
            // Antes cancelar una orden entregada anulaba la factura pero el stock
            // descontado (createExit al entregar) NUNCA volvia -> inventario perdido y
            // COGS no revertido. Ahora, por cada movimiento 'exit' de remision de esta
            // orden, se crea un 'entry' compensatorio (repone stock y postea el asiento
            // inverso DR inventario / CR COGS). Idempotente por reference_type.
            $this->reverseDeliveredStock($salesOrder);

            // Update Sales Order status. invoicing_status es enum sin 'cancelled'
            // (not_invoiced/partial/invoiced): la factura fue anulada -> not_invoiced.
            // financial_status es varchar y si admite 'cancelled'. (5c/E2E)
            $salesOrder->update([
                'invoicing_status' => 'not_invoiced',
                'financial_status' => 'cancelled'
            ]);

            Log::info('SalesOrderCancelledListener: Completed successfully');
        });
    }

    /**
     * Refactor ciclo (5b/F2): repone el stock descontado por las entregas de la orden.
     *
     * Busca los movimientos 'exit' con reference_type='remission' de las remisiones de
     * esta orden y crea un 'entry' compensatorio por cada uno (misma cantidad, warehouse
     * y unit_cost). El 'entry' repone Stock.quantity y, via InventoryMovementCreated,
     * postea el asiento inverso (DR inventario / CR COGS).
     *
     * Idempotente: marca la reversa con reference_type='sales_cancel' + reference_id =
     * el id del movimiento exit original, para no reponer dos veces.
     */
    private function reverseDeliveredStock($salesOrder): void
    {
        $remissionIds = $salesOrder->remissions()->pluck('id');
        if ($remissionIds->isEmpty()) {
            return;
        }

        $exitMovements = InventoryMovement::where('reference_type', 'remission')
            ->whereIn('reference_id', $remissionIds)
            ->where('movement_type', InventoryMovement::MOVEMENT_TYPE_EXIT)
            ->get();

        foreach ($exitMovements as $exit) {
            // R1: idempotencia via idempotency_key (UNIQUE en BD) dentro de createEntry.
            try {
                $this->inventoryMovementService->createEntry([
                    'product_id' => $exit->product_id,
                    'warehouse_id' => $exit->warehouse_id,
                    'quantity' => $exit->quantity,
                    'unit_cost' => $exit->unit_cost,
                    'reference_type' => 'sales_cancel',
                    'reference_id' => $exit->id,
                    'idempotency_key' => "sales_cancel:exit:{$exit->id}",
                    'movement_date' => now(),
                    'description' => "Reposicion por cancelacion de venta {$salesOrder->order_number}",
                    'user_id' => auth()->id() ?? \Modules\User\Models\User::first()?->id ?? 1,
                    'metadata' => [
                        'sales_order_id' => $salesOrder->id,
                        'reversed_exit_movement_id' => $exit->id,
                        'reversal' => true,
                    ],
                ]);
            } catch (\Throwable $e) {
                // No bloquear la cancelacion: registrar para tratamiento manual.
                Log::error('SalesOrderCancelledListener: no se pudo reponer stock de la entrega', [
                    'sales_order_id' => $salesOrder->id,
                    'exit_movement_id' => $exit->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
