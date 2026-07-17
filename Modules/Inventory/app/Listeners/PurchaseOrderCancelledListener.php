<?php

namespace Modules\Inventory\Listeners;

use Modules\Purchase\Events\PurchaseOrderCancelled;
use Modules\Inventory\Services\InventoryMovementService;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\Warehouse;
use Illuminate\Support\Facades\Log;

/**
 * PurchaseOrderCancelledListener (Inventory)
 *
 * Refactor ciclo (Patron 2): al cancelar una OC que estuvo 'received', revierte la
 * entrada de stock generando un movimiento 'exit' compensatorio por cada item. Antes
 * no existia -> cancelar una OC recibida dejaba stock fantasma para siempre.
 *
 * Solo actua si previousStatus == 'received' (solo entonces hubo entrada que revertir).
 * Idempotente: no revierte dos veces (reference_type='purchase_cancel').
 */
class PurchaseOrderCancelledListener
{
    public function __construct(
        protected InventoryMovementService $inventoryMovementService
    ) {}

    public function handle(PurchaseOrderCancelled $event): void
    {
        $purchaseOrder = $event->purchaseOrder;

        // Solo hubo entrada de stock si la OC estuvo recibida.
        if ($event->previousStatus !== 'received') {
            return;
        }

        // Idempotencia: no revertir dos veces.
        $alreadyReversed = InventoryMovement::where('reference_type', 'purchase_cancel')
            ->where('reference_id', $purchaseOrder->id)
            ->exists();
        if ($alreadyReversed) {
            Log::info('PurchaseOrderCancelled: stock ya revertido, skip', [
                'purchase_order_id' => $purchaseOrder->id,
            ]);
            return;
        }

        if (!$purchaseOrder->relationLoaded('purchaseOrderItems')) {
            $purchaseOrder->load('purchaseOrderItems');
        }

        $warehouse = $purchaseOrder->warehouse_id
            ? Warehouse::find($purchaseOrder->warehouse_id)
            : Warehouse::where('is_active', true)->first();

        if (!$warehouse) {
            Log::warning('PurchaseOrderCancelled: no hay almacen para revertir', [
                'purchase_order_id' => $purchaseOrder->id,
            ]);
            return;
        }

        foreach ($purchaseOrder->purchaseOrderItems as $item) {
            // Revertir solo lo recibido (received_quantity), no lo ordenado.
            $qty = (float) ($item->received_quantity ?? $item->quantity);
            if ($qty <= 0) {
                continue;
            }

            try {
                $this->inventoryMovementService->createExit([
                    'product_id' => $item->product_id,
                    'warehouse_id' => $warehouse->id,
                    'quantity' => $qty,
                    'unit_cost' => $item->unit_price,
                    'reference_type' => 'purchase_cancel',
                    'reference_id' => $purchaseOrder->id,
                    'movement_date' => now(),
                    'description' => "Reversa por cancelacion de OC #{$purchaseOrder->id}",
                    'user_id' => auth()->id() ?? \Modules\User\Models\User::first()?->id ?? 1,
                    'metadata' => [
                        'purchase_order_id' => $purchaseOrder->id,
                        'reversal' => true,
                    ],
                    // Exit del sistema (reversa), no requiere inspeccion manual (IV-009).
                    'quality_checked' => true,
                ]);

                Log::info('PurchaseOrderCancelled: entrada de stock revertida', [
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_id' => $item->product_id,
                    'quantity' => $qty,
                ]);
            } catch (\Exception $e) {
                // Refactor ciclo (5b/F4): la reversa de stock no debe bloquear la
                // cancelacion, pero NO puede quedarse solo en un log (antes se perdia).
                // Si el stock ya se vendio y no se puede revertir, se MARCA la OC con un
                // flag accionable en metadata (stock_reversal_pending) para forzar
                // tratamiento manual (nota de credito de compra / ajuste de inventario).
                Log::error('PurchaseOrderCancelled: no se pudo revertir stock', [
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_id' => $item->product_id,
                    'error' => $e->getMessage(),
                ]);

                $meta = $purchaseOrder->metadata ?? [];
                $pending = $meta['stock_reversal_pending'] ?? [];
                $pending[] = [
                    'product_id' => $item->product_id,
                    'quantity' => $qty,
                    'reason' => $e->getMessage(),
                    'flagged_at' => now()->toIso8601String(),
                ];
                $meta['stock_reversal_pending'] = $pending;
                $purchaseOrder->update(['metadata' => $meta]);
            }
        }
    }
}
