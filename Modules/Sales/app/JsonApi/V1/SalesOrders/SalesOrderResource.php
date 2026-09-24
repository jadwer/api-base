<?php

namespace Modules\Sales\JsonApi\V1\SalesOrders;

use LaravelJsonApi\Core\Resources\JsonApiResource;

/**
 * E2E dev 2026-09-18 (bloques 1 y 2): este Resource mantenia a mano las listas
 * de atributos y relaciones y PISABA al Schema: orderType, customerPoNumber,
 * paymentMethod, creditDays, currency, quote, shipments y branch nunca
 * viajaban aunque el Schema los declarara (regla 8 del CLAUDE.md, tercera vez
 * que el patron reaparece aqui). Sin overrides, JsonApiResource serializa
 * exactamente lo que el Schema declara; si un dia hay que ocultar algo, se
 * hace en el Schema con ->hidden(), no aqui.
 */
class SalesOrderResource extends JsonApiResource
{
}
