<?php

namespace Modules\Sales\Services;

use Illuminate\Support\Collection;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\QuoteItem;

/**
 * Reglas de la cotizacion que antes vivian en QuoteController (flujo guiado,
 * F2). Cada metodo `*Problems()` devuelve lo que falta para la accion, sin
 * ejecutarla ni lanzar: ['campo' => 'mensaje en espanol']. El controlador las
 * usa para responder (mismos textos y codigos que antes) y la guia para
 * mostrarlas como bloqueos. "Lo que falta sale del servicio, nunca de reglas
 * copiadas" (atomo/guide).
 *
 * `$intent` es lo que el usuario eligio en el formulario cuando ya se conoce
 * (order_type al convertir); sin intencion se describe el camino por omision.
 */
class QuoteWorkflowService
{
    public function __construct(private StockAvailabilityService $stock) {}

    /** @return array<string, string> */
    public function sendProblems(Quote $quote): array
    {
        if (!$quote->canBeSent) {
            return ['status' => 'La cotización no se puede enviar: debe estar en borrador y tener al menos una partida.'];
        }

        return [];
    }

    /**
     * El vendedor acepta desde borrador o enviada (la aceptacion llega por
     * telefono o mostrador, decision 2026-09-10); el cliente del portal solo
     * una enviada y sin precios por confirmar (D9 granel).
     *
     * @return array<string, string>
     */
    public function acceptProblems(Quote $quote, bool $asStaff): array
    {
        if ($quote->status === 'expired' || ($quote->status === 'sent' && $quote->isExpired)) {
            return ['status' => 'La cotización venció el ' . $quote->valid_until?->format('d/m/Y') . '. Duplícala para renovar la vigencia.'];
        }
        $acceptable = $asStaff ? ['draft', 'sent'] : ['sent'];
        if (!in_array($quote->status, $acceptable, true)) {
            return ['status' => 'Esta cotización no se puede aceptar en su estado actual.'];
        }
        if (!$asStaff && ($message = $this->pricePendingMessage($quote, 'aceptar'))) {
            return ['price_pending' => $message];
        }

        return [];
    }

    /** @return array<string, string> */
    public function rejectProblems(Quote $quote): array
    {
        if (!in_array($quote->status, ['sent', 'draft'], true)) {
            return ['status' => 'Esta cotización no se puede rechazar en su estado actual.'];
        }

        return [];
    }

    /**
     * @param  array{order_type?: string|null}  $intent
     * @return array<string, string>
     */
    public function convertProblems(Quote $quote, array $intent = []): array
    {
        if (!$quote->canBeConverted) {
            return ['status' => 'La cotización no se puede convertir: debe estar aceptada y no haberse convertido antes.'];
        }
        if ($message = $this->pricePendingMessage($quote, 'convertir')) {
            return ['price_pending' => $message];
        }
        // El stock solo aplica a la venta directa; el pedido aparta y deja backorder.
        if (($intent['order_type'] ?? null) === 'direct_sale' && $this->insufficientStock($quote) !== []) {
            return ['stock' => 'Stock insuficiente para venta directa'];
        }

        return [];
    }

    /** @return array<string, string> */
    public function cancelProblems(Quote $quote): array
    {
        if (in_array($quote->status, ['converted', 'cancelled'], true)) {
            return ['status' => 'Esta cotización no se puede cancelar en su estado actual.'];
        }

        return [];
    }

    /**
     * Partidas con precio por confirmar (granel, D8): precio efectivo <= 0.
     *
     * @return Collection<int, QuoteItem>
     */
    public function pendingPriceItems(Quote $quote): Collection
    {
        $quote->loadMissing('items.product');

        return $quote->items->filter(fn (QuoteItem $item) => $item->price_pending)->values();
    }

    /** Mensaje de precio por confirmar para `aceptar` o `convertir`, o null si no aplica. */
    public function pricePendingMessage(Quote $quote, string $action): ?string
    {
        $pending = $this->pendingPriceItems($quote);
        if ($pending->isEmpty()) {
            return null;
        }
        $names = $pending->map(fn (QuoteItem $item) => $this->itemName($item));

        return $action === 'aceptar'
            ? 'Esta cotización tiene productos con precio por confirmar: ' . $names->implode(', ') . '. Podrás aceptarla cuando el vendedor capture el precio.'
            : 'Captura el precio de venta (mayor a $0) de estos productos antes de convertir: ' . $names->implode(', ') . '.';
    }

    /** Frase para la guia: cuantas partidas esperan precio ("Confirma el precio de 2 partidas"). */
    public function pendingPriceCount(Quote $quote): int
    {
        return $this->pendingPriceItems($quote)->count();
    }

    public function itemName(QuoteItem $item): string
    {
        return $item->product_name ?? $item->product->name ?? ('#' . $item->product_id);
    }

    /** @return array<int, mixed> partidas sin existencia para venta directa */
    public function insufficientStock(Quote $quote): array
    {
        $quote->loadMissing('items.product');

        return $this->stock->insufficientItems($quote->items);
    }
}
