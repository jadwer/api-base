<?php

namespace Modules\Reports\Services\SalesReports;

use Carbon\Carbon;
use Modules\Sales\Models\SalesOrderItem;
use Illuminate\Support\Facades\DB;

class SalesByProductReportService
{
    /**
     * Generate Sales by Product Report
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string $currency
     * @return array
     */
    public function generate(Carbon $startDate, Carbon $endDate, string $currency = 'MXN'): array
    {
        $salesItems = SalesOrderItem::whereHas('salesOrder', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('order_date', [$startDate, $endDate])
                    ->whereIn('status', ['completed', 'delivered']);
            })
            ->with(['product', 'salesOrder'])
            ->get();

        $salesByProduct = $salesItems->groupBy('product_id')->map(function ($productItems) {
            $product = $productItems->first()->product;

            $totalQuantity = $productItems->sum('quantity');
            $totalAmount = $productItems->sum(function ($item) {
                return $item->quantity * $item->unit_price;
            });

            return [
                'product_id' => $product->id ?? null,
                'product_code' => $product->code ?? 'Unknown',
                'product_name' => $product->name ?? 'Unknown',
                'total_quantity' => $totalQuantity,
                'total_amount' => round($totalAmount, 2),
                'average_price' => $totalQuantity > 0 
                    ? round($totalAmount / $totalQuantity, 2) 
                    : 0,
                'order_count' => $productItems->unique('sales_order_id')->count(),
            ];
        })->sortByDesc('total_amount')->values();

        $totalSales = $salesByProduct->sum('total_amount');
        $totalQuantity = $salesByProduct->sum('total_quantity');

        return [
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
            'currency' => $currency,
            'salesByProduct' => $salesByProduct->toArray(),
            'summary' => [
                'total_products' => $salesByProduct->count(),
                'total_quantity' => $totalQuantity,
                'total_sales' => round($totalSales, 2),
                'average_per_product' => $salesByProduct->count() > 0 
                    ? round($totalSales / $salesByProduct->count(), 2) 
                    : 0,
            ],
            'generatedAt' => now()->toIso8601String(),
        ];
    }
}
