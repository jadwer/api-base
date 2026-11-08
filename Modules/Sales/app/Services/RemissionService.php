<?php

namespace Modules\Sales\Services;

use Modules\Inventory\Models\Stock;
use Modules\Sales\Exceptions\RemissionRuleException;
use Modules\Sales\Models\Backorder;
use Modules\Sales\Models\Remission;
use Modules\Sales\Models\RemissionItem;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;

/**
 * Surtido F2/F4: reglas para crear remisiones desde un pedido. Las usan el
 * controlador de remisiones y el surtido de backorders (que se surten por
 * remision). El llamador abre la transaccion y bloquea el pedido.
 */
class RemissionService
{
    public function __construct(private OrderStatusService $orderStatusService)
    {
    }

    /**
     * Crea la remision con sus partidas. $lines: [[SalesOrderItem, cantidad, datos extra]].
     *
     * @param array<int, array{0: SalesOrderItem, 1: float, 2?: array<string, mixed>}> $lines
     * @param array<string, mixed> $attributes
     * @param bool $useOwnReservation false para surtir un backorder: lo apartado del
     *        pedido es de la parte que si tenia existencia, no del faltante.
     * @throws RemissionRuleException
     */
    public function create(SalesOrder $order, array $lines, array $attributes = [], bool $useOwnReservation = true): Remission
    {
        $this->assertOrderCanBeRemitted($order);

        foreach ($lines as [$orderItem, $quantity]) {
            // Lo pendiente descuenta remitido, embarcado y envios abiertos.
            $pending = $orderItem->pendingOutboundQuantity();
            if ($quantity > $pending + 0.00001) {
                throw new RemissionRuleException(
                    "La cantidad {$quantity} excede lo pendiente por surtir ({$pending}) de la partida {$orderItem->id}."
                );
            }
        }

        $this->assertStock($order, $lines, $attributes['warehouse_id'] ?? null, $useOwnReservation);

        $remission = Remission::create(array_merge([
            'sales_order_id' => $order->id,
            'remission_date' => now(),
        ], $attributes));

        foreach ($lines as $line) {
            [$orderItem, $quantity] = $line;
            $extra = $line[2] ?? [];
            RemissionItem::create([
                'remission_id' => $remission->id,
                'sales_order_item_id' => $orderItem->id,
                'product_id' => $orderItem->product_id,
                'quantity' => $quantity,
                'product_name' => $orderItem->product->name,
                'product_sku' => $orderItem->product->sku,
                'unit' => $orderItem->product->unit?->name,
                'batch_number' => $extra['batch_number'] ?? null,
                'expiry_date' => $extra['expiry_date'] ?? null,
                'notes' => $extra['notes'] ?? null,
            ]);
        }

        return $remission;
    }

    /**
     * Partidas para la remision completa: lo pendiente de cada partida menos lo
     * que espera en backorder (F4: eso se surte por su propia remision cuando
     * llega la mercancia).
     *
     * @return array<int, array{0: SalesOrderItem, 1: float}>
     */
    public function fullLines(SalesOrder $order): array
    {
        $lines = [];
        foreach ($order->items as $orderItem) {
            /** @var SalesOrderItem $orderItem */
            $backordered = (float) Backorder::where('sales_order_item_id', $orderItem->id)
                ->pending()
                ->get()
                ->sum(fn (Backorder $b) => $b->remaining_quantity);
            $quantity = round($orderItem->pendingOutboundQuantity() - $backordered, 4);
            if ($quantity > 0) {
                $lines[] = [$orderItem, $quantity];
            }
        }

        return $lines;
    }

    /**
     * Solo se remisiona un pedido confirmado, en proceso o enviado.
     *
     * @throws RemissionRuleException
     */
    public function assertOrderCanBeRemitted(SalesOrder $order): void
    {
        foreach ($this->remitProblems($order, withPending: false) as $message) {
            throw new RemissionRuleException($message);
        }
    }

    /**
     * Lo que falta para remisionar el pedido (flujo guiado F2): estado y, si se
     * pide, que queden partidas pendientes (sin lo que espera en backorder).
     *
     * @return array<string, string>
     */
    public function remitProblems(SalesOrder $order, bool $withPending = true): array
    {
        // Mismo orden que el endpoint from-order-full: partidas, estado, pendiente.
        if ($withPending) {
            $order->loadMissing('items.product');
            if ($order->items->isEmpty()) {
                return ['items' => 'No se puede crear una remisión de un pedido sin partidas.'];
            }
        }
        if (!in_array($order->status, ['confirmed', 'processing', 'shipped'], true)) {
            return ['status' => "El pedido debe estar confirmado, en proceso o enviado para remisionarse. Estado actual: {$order->status}."];
        }
        if ($withPending && empty($this->fullLines($order))) {
            return ['pending' => 'El pedido ya no tiene partidas pendientes por surtir.'];
        }

        return [];
    }

    /** @return array<string, string> */
    public function printProblems(Remission $remission): array
    {
        return $remission->canBePrinted() ? [] : ['status' => 'La remisión no se puede imprimir: debe tener partidas y estar en borrador o impresa.'];
    }

    /** @return array<string, string> */
    public function deliverProblems(Remission $remission): array
    {
        return $remission->canBeDelivered() ? [] : ['status' => 'La remisión no se puede marcar como entregada: primero debe imprimirse.'];
    }

    /** @return array<string, string> */
    public function cancelProblems(Remission $remission): array
    {
        return $remission->status === 'delivered' ? ['status' => 'Esta remisión no se puede cancelar: ya fue entregada.'] : [];
    }

    /**
     * Almacen de salida de una partida: remision -> almacen donde el pedido la
     * aparto -> el de mas existencia libre.
     */
    public function resolveWarehouseId(?int $warehouseId, SalesOrder $order, int $orderItemId, int $productId): ?int
    {
        if ($warehouseId) {
            return $warehouseId;
        }

        $stockId = $this->orderStatusService->reservationStockId($order, $orderItemId);
        if ($stockId && ($reserved = Stock::find($stockId))) {
            return $reserved->warehouse_id;
        }

        return Stock::where('product_id', $productId)
            ->orderByRaw('(quantity - reserved_quantity) desc')
            ->value('warehouse_id');
    }

    /**
     * Existencia por almacen con lock. Lo apartado por ESTE pedido cuenta como
     * disponible para el.
     *
     * @param array<int, array{0: SalesOrderItem, 1: float, 2?: mixed}> $lines
     * @throws RemissionRuleException
     */
    public function assertStock(SalesOrder $order, array $lines, ?int $warehouseId, bool $useOwnReservation = true): void
    {
        $needed = [];

        foreach ($lines as [$orderItem, $quantity]) {
            $wh = $this->resolveWarehouseId($warehouseId, $order, $orderItem->id, $orderItem->product_id);
            $key = $orderItem->product_id . ':' . ($wh ?? 0);
            $needed[$key] ??= ['item' => $orderItem, 'warehouse_id' => $wh, 'quantity' => 0.0];
            $needed[$key]['quantity'] += $quantity;
        }

        $insufficient = [];
        foreach ($needed as $need) {
            $orderItem = $need['item'];
            $stock = $need['warehouse_id']
                ? Stock::where('product_id', $orderItem->product_id)
                    ->where('warehouse_id', $need['warehouse_id'])
                    ->lockForUpdate()
                    ->first()
                : null;

            $available = 0.0;
            if ($stock) {
                $available = (float) $stock->quantity - (float) $stock->reserved_quantity;
                $reservations = $useOwnReservation ? ($order->metadata['reservations'] ?? []) : [];
                foreach ($reservations as $itemId => $entry) {
                    if ((int) ($entry['stock_id'] ?? 0) === $stock->id) {
                        $available += (float) ($entry['quantity'] ?? 0);
                    }
                }
            }

            if ($available + 0.00001 < $need['quantity']) {
                $insufficient[] = [
                    'product_id' => $orderItem->product_id,
                    'product_name' => $orderItem->product->name ?? 'Producto',
                    'sku' => $orderItem->product->sku ?? '',
                    'warehouse_id' => $need['warehouse_id'],
                    'required' => $need['quantity'],
                    'available' => max(0.0, $available),
                    'deficit' => $need['quantity'] - max(0.0, $available),
                ];
            }
        }

        if (!empty($insufficient)) {
            throw new RemissionRuleException('Stock insuficiente para generar la remisión', [
                'insufficient_items' => $insufficient,
                'suggestion' => 'Genere una Orden de Compra para reponer el inventario antes de crear la remisión.',
            ]);
        }
    }
}
