<?php

namespace Modules\Sales\Services;

use Modules\Inventory\Models\Stock;

/**
 * Fase A - Venta directa vs Pedido.
 *
 * Calculo de disponibilidad de stock compartido entre:
 * - GET sales-orders/{id}/stock-availability (endpoint existente)
 * - POST quotes/{id}/convert con order_type=direct_sale (bloqueo por faltantes)
 * - POST sales-orders/{id}/facturar (regla fiscal: sin stock no hay factura)
 *
 * Acepta items de cotizacion (QuoteItem) o de orden (SalesOrderItem):
 * solo requiere que cada item exponga product_id, quantity y, opcionalmente,
 * la relacion product o el snapshot product_name.
 */
class StockAvailabilityService
{
    /**
     * Check stock availability for a set of items.
     *
     * @param iterable $items QuoteItem[] or SalesOrderItem[]
     * @return array{items: array<int, array<string, mixed>>, all_sufficient: bool}
     */
    public function check(iterable $items): array
    {
        $result = [];
        $allSufficient = true;

        foreach ($items as $item) {
            $availableQty = Stock::where('product_id', $item->product_id)
                ->sum('available_quantity');

            $isSufficient = $availableQty >= $item->quantity;
            if (!$isSufficient) {
                $allSufficient = false;
            }

            $result[] = [
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name ?? $item->product_name ?? 'Producto',
                'sku' => $item->product?->sku ?? '',
                'required_quantity' => (float) $item->quantity,
                'available_quantity' => (float) $availableQty,
                'is_sufficient' => $isSufficient,
                'deficit' => $isSufficient ? 0 : (float) ($item->quantity - $availableQty),
            ];
        }

        return [
            'items' => $result,
            'all_sufficient' => $allSufficient,
        ];
    }

    /**
     * Items whose requested quantity exceeds available stock, in the
     * error format shared by convert (direct_sale) and facturar:
     * [{product_id, product_name, requested, available}]
     *
     * @param iterable $items QuoteItem[] or SalesOrderItem[]
     * @return array<int, array{product_id: int, product_name: string, requested: float, available: float}>
     */
    public function insufficientItems(iterable $items): array
    {
        $insufficient = [];

        foreach ($this->check($items)['items'] as $item) {
            if ($item['is_sufficient']) {
                continue;
            }

            $insufficient[] = [
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'requested' => $item['required_quantity'],
                'available' => $item['available_quantity'],
            ];
        }

        return $insufficient;
    }
}
