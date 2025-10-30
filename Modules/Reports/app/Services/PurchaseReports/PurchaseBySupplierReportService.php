<?php

namespace Modules\Reports\Services\PurchaseReports;

use Carbon\Carbon;
use Modules\Purchase\Models\PurchaseOrder;

class PurchaseBySupplierReportService
{
    /**
     * Generate Purchase by Supplier Report
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string $currency
     * @return array
     */
    public function generate(Carbon $startDate, Carbon $endDate, string $currency = 'MXN'): array
    {
        $purchases = PurchaseOrder::whereBetween('order_date', [$startDate, $endDate])
            ->whereIn('status', ['completed', 'received'])
            ->with('supplier')
            ->get();

        $purchasesBySupplier = $purchases->groupBy('supplier_id')->map(function ($supplierOrders) {
            $supplier = $supplierOrders->first()->supplier;

            return [
                'supplier_id' => $supplier->id ?? null,
                'supplier_name' => $supplier->name ?? 'Unknown',
                'total_orders' => $supplierOrders->count(),
                'total_amount' => round($supplierOrders->sum('total_amount'), 2),
                'orders' => $supplierOrders->map(function ($order) {
                    return [
                        'order_number' => $order->order_number,
                        'order_date' => $order->order_date,
                        'total_amount' => round($order->total_amount, 2),
                        'status' => $order->status,
                    ];
                })->toArray(),
            ];
        })->sortByDesc('total_amount')->values();

        $totalPurchases = $purchasesBySupplier->sum('total_amount');
        $totalOrders = $purchasesBySupplier->sum('total_orders');

        return [
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
            'currency' => $currency,
            'purchasesBySupplier' => $purchasesBySupplier->toArray(),
            'summary' => [
                'total_suppliers' => $purchasesBySupplier->count(),
                'total_orders' => $totalOrders,
                'total_purchases' => round($totalPurchases, 2),
                'average_per_supplier' => $purchasesBySupplier->count() > 0 
                    ? round($totalPurchases / $purchasesBySupplier->count(), 2) 
                    : 0,
            ],
            'generatedAt' => now()->toIso8601String(),
        ];
    }
}
