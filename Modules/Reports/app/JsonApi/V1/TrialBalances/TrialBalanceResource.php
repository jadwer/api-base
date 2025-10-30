<?php

namespace Modules\Reports\JsonApi\V1\TrialBalances;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class TrialBalanceResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        $data = is_object($this->resource) ? (array) $this->resource : $this->resource;

        return [
            'asOfDate' => $data['as_of_date'] ?? null,
            'currency' => $data['currency'] ?? 'MXN',
            'accounts' => $data['accounts'] ?? [],
            'totals' => $data['totals'] ?? [],
            'summaryByType' => $data['summary_by_type'] ?? [],
            'balanced' => $data['totals']['balanced'] ?? false,
            'generatedAt' => now()->toISOString(),
        ];
    }

    public function relationships($request): iterable
    {
        return [];
    }
}
