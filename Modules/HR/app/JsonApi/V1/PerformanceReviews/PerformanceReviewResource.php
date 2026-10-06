<?php

namespace Modules\HR\JsonApi\V1\PerformanceReviews;

use LaravelJsonApi\Core\Resources\JsonApiResource;

/**
 * La salida la define el Schema (fuente unica, 2026-09-30).
 *
 * Antes este Resource repetia a mano la lista de campos y se desalineaba del
 * Schema: campos que se guardaban y nunca se devolvian, y los ->hidden() del
 * Schema no aplicaban. Si hace falta un campo calculado, va en el Schema
 * (campo de solo lectura) o como accesor del modelo; si de verdad se
 * necesita logica aqui, partir de parent::attributes($request).
 * El candado de la salida es tests/Feature/Contracts/JsonApiContractTest.
 */
class PerformanceReviewResource extends JsonApiResource
{
}
