<?php

namespace Modules\Reports\JsonApi\V1\CashFlows;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class CashFlowResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        $data = is_object($this->resource) ? (array) $this->resource : $this->resource;

        return [
            'period' => $data['period'] ?? [],
            'currency' => $data['currency'] ?? 'MXN',
            'operatingActivities' => $data['operating_activities'] ?? [],
            'netCashFromOperating' => $data['operating_activities']['net_cash_from_operations'] ?? 0,
            'investingActivities' => $data['investing_activities'] ?? [],
            'netCashFromInvesting' => $data['investing_activities']['net_cash_from_investing'] ?? 0,
            'financingActivities' => $data['financing_activities'] ?? [],
            'netCashFromFinancing' => $data['financing_activities']['net_cash_from_financing'] ?? 0,
            'netCashChange' => $data['net_change_in_cash'] ?? 0,
            'beginningCash' => $data['beginning_cash'] ?? 0,
            'endingCash' => $data['ending_cash'] ?? 0,
            'generatedAt' => now()->toISOString(),
        ];
    }

    public function relationships($request): iterable
    {
        return [];
    }
}
