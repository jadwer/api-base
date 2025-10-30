<?php

namespace Modules\Reports\Services\PurchaseReports;

use Carbon\Carbon;
use Modules\Purchase\Models\PurchaseOrderItem;

class PurchaseByProductReportService
{
    /**
     * Generate Purchase by Product Report
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string $currency
     * @return array
     */
    public function generate(Carbon $startDate, Carbon $endDate, string $currency = 'MXN'): array
    {
        $purchaseItems = PurchaseOrderItem::whereHas('purchaseOrder', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('order_date', [$startDate, $endDate])
                    ->whereIn('status', ['completed', 'received']);
            })
            ->with(['product', 'purchaseOrder'])
            ->get();

        $purchasesByProduct = $purchaseItems->groupBy('product_id')->map(function ($productItems) {
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
                'order_count' => $productItems->unique('purchase_order_id')->count(),
            ];
        })->sortByDesc('total_amount')->values();

        $totalPurchases = $purchasesByProduct->sum('total_amount');
        $totalQuantity = $purchasesByProduct->sum('total_quantity');

        return [
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
            'currency' => $currency,
            'purchasesByProduct' => $purchasesByProduct->toArray(),
            'summary' => [
                'total_products' => $purchasesByProduct->count(),
                'total_quantity' => $totalQuantity,
                'total_purchases' => round($totalPurchases, 2),
                'average_per_product' => $purchasesByProduct->count() > 0 
                    ? round($totalPurchases / $purchasesByProduct->count(), 2) 
                    : 0,
            ],
            'generatedAt' => now()->toIso8601String(),
        ];
    }
}
