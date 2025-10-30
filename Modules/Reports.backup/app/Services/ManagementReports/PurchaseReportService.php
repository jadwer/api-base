<?php

namespace Modules\Reports\Services\ManagementReports;

use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseOrderItem;
use Carbon\Carbon;

class PurchaseReportService
{
    /**
     * Generate Purchase by Supplier Report
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function generateBySupplier(Carbon $startDate, Carbon $endDate): array
    {
        $purchaseOrders = PurchaseOrder::whereBetween('order_date', [
            $startDate->toDateString(),
            $endDate->toDateString()
        ])
            ->whereIn('status', ['approved', 'received'])
            ->with('supplier')
            ->get();

        $supplierPurchases = [];
        $totalPurchases = 0;

        foreach ($purchaseOrders as $order) {
            $supplierId = $order->supplier_id;
            $supplierName = $order->supplier->name ?? 'Unknown Supplier';

            if (!isset($supplierPurchases[$supplierId])) {
                $supplierPurchases[$supplierId] = [
                    'supplier_id' => $supplierId,
                    'supplier_name' => $supplierName,
                    'order_count' => 0,
                    'total_purchases' => 0,
                ];
            }

            $supplierPurchases[$supplierId]['order_count']++;
            $supplierPurchases[$supplierId]['total_purchases'] += $order->total_amount;
            $totalPurchases += $order->total_amount;
        }

        // Sort by total purchases descending
        $supplierPurchases = collect($supplierPurchases)->sortByDesc('total_purchases')->values()->all();

        return [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'report_type' => 'purchase_by_supplier',
            'currency' => config('app.currency', 'MXN'),
            'suppliers' => $supplierPurchases,
            'summary' => [
                'total_suppliers' => count($supplierPurchases),
                'total_orders' => $purchaseOrders->count(),
                'total_purchases' => $totalPurchases,
            ],
        ];
    }

    /**
     * Generate Purchase by Product Report
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function generateByProduct(Carbon $startDate, Carbon $endDate): array
    {
        $orderItems = PurchaseOrderItem::whereHas('purchaseOrder', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('order_date', [
                $startDate->toDateString(),
                $endDate->toDateString()
            ])
                ->whereIn('status', ['approved', 'received']);
        })
            ->with('product')
            ->get();

        $productPurchases = [];
        $totalQuantity = 0;
        $totalCost = 0;

        foreach ($orderItems as $item) {
            $productId = $item->product_id;
            $productName = $item->product->name ?? 'Unknown Product';
            $productCode = $item->product->code ?? '';

            if (!isset($productPurchases[$productId])) {
                $productPurchases[$productId] = [
                    'product_id' => $productId,
                    'product_code' => $productCode,
                    'product_name' => $productName,
                    'quantity_purchased' => 0,
                    'total_cost' => 0,
                ];
            }

            $productPurchases[$productId]['quantity_purchased'] += $item->quantity;
            $productPurchases[$productId]['total_cost'] += $item->subtotal;
            $totalQuantity += $item->quantity;
            $totalCost += $item->subtotal;
        }

        // Sort by total cost descending
        $productPurchases = collect($productPurchases)->sortByDesc('total_cost')->values()->all();

        return [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'report_type' => 'purchase_by_product',
            'currency' => config('app.currency', 'MXN'),
            'products' => $productPurchases,
            'summary' => [
                'total_products' => count($productPurchases),
                'total_quantity_purchased' => $totalQuantity,
                'total_cost' => $totalCost,
            ],
        ];
    }
}
