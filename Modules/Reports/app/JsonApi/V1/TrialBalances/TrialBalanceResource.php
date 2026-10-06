<?php

namespace Modules\Reports\JsonApi\V1\TrialBalances;

use LaravelJsonApi\Core\Resources\JsonApiResource;

/**
 * @serializacion-propia La entidad no es un modelo Eloquent: el reporte se
 * calcula y este Resource arma la salida desde el arreglo de datos. Aqui SI
 * aplica un Resource con attributes() propio (regla 2026-09-30).
 */
class TrialBalanceResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        $data = is_object($this->resource) ? (array) $this->resource : $this->resource;

        return [
            'asOfDate' => $data['asOfDate'] ?? $data['as_of_date'] ?? null,
            'currency' => $data['currency'] ?? 'MXN',
            'accounts' => $data['accounts'] ?? [],
            'totals' => $data['totals'] ?? [],
            'summaryByType' => $data['summaryByType'] ?? $data['summary_by_type'] ?? [],
            'balanced' => $data['balanced'] ?? false,
            'generatedAt' => $data['generatedAt'] ?? now()->toISOString(),
        ];
    }

    public function relationships($request): iterable
    {
        return [];
    }
}
