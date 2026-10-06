<?php

namespace Modules\Reports\JsonApi\V1\IncomeStatements;

use LaravelJsonApi\Core\Resources\JsonApiResource;

/**
 * @serializacion-propia La entidad no es un modelo Eloquent: el reporte se
 * calcula y este Resource arma la salida desde el arreglo de datos. Aqui SI
 * aplica un Resource con attributes() propio (regla 2026-09-30).
 */
class IncomeStatementResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        $data = is_object($this->resource) ? (array) $this->resource : $this->resource;

        return [
            'period' => $data['period'] ?? [],
            'currency' => $data['currency'] ?? 'MXN',
            'revenue' => $data['revenue'] ?? [],
            'costOfGoodsSold' => $data['cost_of_goods_sold'] ?? 0,
            'grossProfit' => $data['gross_profit'] ?? 0,
            'grossProfitMargin' => $data['gross_profit_margin'] ?? 0,
            'operatingExpenses' => $data['operating_expenses'] ?? [],
            'operatingIncome' => $data['operating_income'] ?? 0,
            'operatingMargin' => $data['operating_margin'] ?? 0,
            'otherIncomeExpenses' => $data['other_income_expenses'] ?? [],
            'netIncome' => $data['net_income'] ?? 0,
            'netProfitMargin' => $data['net_profit_margin'] ?? 0,
            'generatedAt' => now()->toISOString(),
        ];
    }

    public function relationships($request): iterable
    {
        return [];
    }
}
