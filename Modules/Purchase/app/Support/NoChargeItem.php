<?php

namespace Modules\Purchase\Support;

/**
 * Partida de compra sin costo (muestra, bonificacion, reposicion por garantia).
 *
 * Una partida a costo 0 solo se acepta marcada asi (metadata.no_charge en
 * JSON:API, items.*.no_charge en el alta atomica). Sin la marca, un precio
 * olvidado entraba al inventario a costo 0 y bajaba el costo promedio sin
 * que nadie lo notara (VAM 2026-10-28).
 */
final class NoChargeItem
{
    public const MESSAGE = 'El costo unitario debe ser mayor a 0. Si la partida no tiene costo (muestra o bonificación), márcala como sin costo.';
}
