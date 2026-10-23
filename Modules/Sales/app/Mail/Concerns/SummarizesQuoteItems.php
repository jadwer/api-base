<?php

namespace Modules\Sales\Mail\Concerns;

use Modules\Sales\Models\Quote;

/**
 * Resumen de lineas para los correos de cotizacion (vista blade y plantilla
 * de Mailer Manager).
 *
 * La plantilla recorre {{#each items}} con {name, sku, quantity, price,
 * unit_price, subtotal}; antes recibia un texto plano y la tabla salia vacia.
 * P2 granel: una linea con precio pendiente lleva "Por confirmar" en precio y
 * subtotal (VariableReplacer imprime tal cual un texto no numerico).
 */
trait SummarizesQuoteItems
{
    public const PRICE_PENDING_TEXT = 'Por confirmar';

    public const PRICE_PENDING_NOTICE = 'Precio sujeto a cotización: los productos marcados "Por confirmar" no están incluidos en el total. Le confirmaremos el precio antes de procesar su pedido.';

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function quoteItemsSummary(Quote $quote): array
    {
        return $quote->items->map(function ($item) {
            $pending = (bool) $item->price_pending;

            return [
                'name' => $item->product_name ?: ($item->product?->name ?: 'Producto'),
                'sku' => $item->product_sku ?? $item->product?->sku ?? '-',
                'quantity' => $item->quantity,
                'unit_price' => $item->quoted_price,
                'subtotal' => $item->total,
                'notes' => $item->notes,
                'price_pending' => $pending,
            ];
        })->toArray();
    }

    /**
     * Lineas para la plantilla: montos numericos (la plantilla aplica
     * |currency) o el texto de precio pendiente.
     *
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    protected function templateItems(array $items): array
    {
        return array_map(function (array $item) {
            $price = $item['price_pending'] ? self::PRICE_PENDING_TEXT : (float) $item['unit_price'];
            $subtotal = $item['price_pending'] ? self::PRICE_PENDING_TEXT : (float) $item['subtotal'];

            return [
                'name' => $item['name'],
                'sku' => $item['sku'],
                'quantity' => $item['quantity'],
                'price' => $price,
                'unit_price' => $price,
                'subtotal' => $subtotal,
            ];
        }, $items);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    protected function pendingPriceNotice(array $items): string
    {
        foreach ($items as $item) {
            if (! empty($item['price_pending'])) {
                return self::PRICE_PENDING_NOTICE;
            }
        }

        return '';
    }
}
