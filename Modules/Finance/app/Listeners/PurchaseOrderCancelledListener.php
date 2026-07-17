<?php

namespace Modules\Finance\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Finance\Models\APInvoice;
use Modules\Accounting\Services\AccountingService;
use Modules\Purchase\Events\PurchaseOrderCancelled;

/**
 * PurchaseOrderCancelledListener (Finance)
 *
 * Refactor ciclo (Patron 2): al cancelar una OC que genero APInvoice (estuvo
 * 'received'), anula esa APInvoice y revierte su asiento GL. Antes no existia ->
 * cancelar una OC recibida dejaba una cuenta por pagar viva sin OC valida.
 *
 * Simetrico a SalesOrderCancelledListener. Si la APInvoice ya tuvo pagos al proveedor,
 * se anula igual pero se deja constancia (requiere ajuste manual con el proveedor).
 */
class PurchaseOrderCancelledListener
{
    public function __construct(
        private AccountingService $accountingService
    ) {}

    public function handle(PurchaseOrderCancelled $event): void
    {
        $purchaseOrder = $event->purchaseOrder;

        // Solo hay APInvoice que anular si la OC estuvo recibida.
        if ($event->previousStatus !== 'received') {
            return;
        }

        try {
            DB::beginTransaction();

            $apInvoice = APInvoice::where('purchase_order_id', $purchaseOrder->id)->first();

            if (!$apInvoice) {
                Log::info('PurchaseOrderCancelled (Finance): no hay APInvoice que anular', [
                    'purchase_order_id' => $purchaseOrder->id,
                ]);
                DB::commit();
                return;
            }

            if ($apInvoice->status === 'voided') {
                DB::commit();
                return; // idempotente
            }

            $hadPayments = (float) ($apInvoice->paid_amount ?? 0) > 0;

            $apInvoice->update([
                'status' => 'voided',
                'voided_at' => now(),
                'void_reason' => "Purchase Order #{$purchaseOrder->order_number} cancelled",
            ]);

            // Revertir el asiento GL si estaba posteado.
            if ($apInvoice->journal_entry_id) {
                $journalEntry = $apInvoice->journalEntry;
                if ($journalEntry && $journalEntry->status === 'posted') {
                    $this->accountingService->reverseJournalEntry(
                        $journalEntry,
                        "Purchase Order #{$purchaseOrder->order_number} cancelled"
                    );
                }
            }

            if ($hadPayments) {
                $apInvoice->update([
                    'notes' => trim(($apInvoice->notes ?? '') . ' | OC CANCELADA con pago al proveedor ('
                        . ($apInvoice->paid_amount ?? 0) . '): requiere ajuste manual con el proveedor.'),
                ]);
                Log::warning('PurchaseOrderCancelled (Finance): APInvoice anulada con pago aplicado', [
                    'ap_invoice_id' => $apInvoice->id,
                    'paid_amount' => $apInvoice->paid_amount ?? 0,
                ]);
            }

            DB::commit();

            Log::info('PurchaseOrderCancelled (Finance): APInvoice anulada + GL revertido', [
                'purchase_order_id' => $purchaseOrder->id,
                'ap_invoice_id' => $apInvoice->id,
                'had_payments' => $hadPayments,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PurchaseOrderCancelled (Finance): fallo al anular APInvoice', [
                'purchase_order_id' => $purchaseOrder->id,
                'error' => $e->getMessage(),
            ]);
            // No re-lanzar: no bloquear la cancelacion de la OC.
        }
    }
}
