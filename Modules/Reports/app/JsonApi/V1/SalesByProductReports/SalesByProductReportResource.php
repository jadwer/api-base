<?php

namespace Modules\Reports\JsonApi\V1\SalesByProductReports;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class SalesByProductReportResource extends JsonApiResource
{
    /**
     * Get the resource's attributes.
     *
     * For virtual resources (reports), $this->resource is an array or stdClass
     * containing the report data
     *
     * @param  \Illuminate\Http\Request|null  $request
     * @return iterable
     */
    public function attributes($request): iterable
    {
        // Convert resource to array if it's an object
        $data = is_object($this->resource) ? (array) $this->resource : $this->resource;

        return [
            'asOfDate' => $data['as_of_date'] ?? $data['asOfDate'] ?? null,
            'currency' => $data['currency'] ?? 'MXN',
            'balanced' => $data['balanced'] ?? false,

            // Assets section
            'assets' => $data['assets'] ?? [],
            'totalAssets' => $data['assets']['total_assets'] ?? 0,

            // Liabilities section
            'liabilities' => $data['liabilities'] ?? [],
            'totalLiabilities' => $data['liabilities']['total_liabilities'] ?? 0,

            // Equity section
            'equity' => $data['equity'] ?? [],
            'totalEquity' => $data['equity']['total_equity'] ?? 0,

            // Generation timestamp
            'generatedAt' => now()->toISOString(),
        ];
    }

    /**
     * Get the resource's relationships.
     *
     * Balance sheets don't have relationships
     *
     * @param  \Illuminate\Http\Request|null  $request
     * @return iterable
     */
    public function relationships($request): iterable
    {
        return [];
    }
}
