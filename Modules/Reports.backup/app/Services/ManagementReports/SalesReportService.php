<?php

namespace Modules\Reports\Services\ManagementReports;

use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SalesReportService
{
    /**
     * Generate Sales by Customer Report
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function generateByCustomer(Carbon $startDate, Carbon $endDate): array
    {
        $salesOrders = SalesOrder::whereBetween('order_date', [
            $startDate->toDateString(),
            $endDate->toDateString()
        ])
            ->whereIn('status', ['confirmed', 'completed'])
            ->with('customer')
            ->get();

        $customerSales = [];
        $totalSales = 0;

        foreach ($salesOrders as $order) {
            $customerId = $order->customer_id;
            $customerName = $order->customer->name ?? 'Unknown Customer';

            if (!isset($customerSales[$customerId])) {
                $customerSales[$customerId] = [
                    'customer_id' => $customerId,
                    'customer_name' => $customerName,
                    'order_count' => 0,
                    'total_sales' => 0,
                ];
            }

            $customerSales[$customerId]['order_count']++;
            $customerSales[$customerId]['total_sales'] += $order->total_amount;
            $totalSales += $order->total_amount;
        }

        // Sort by total sales descending
        $customerSales = collect($customerSales)->sortByDesc('total_sales')->values()->all();

        return [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'report_type' => 'sales_by_customer',
            'currency' => config('app.currency', 'MXN'),
            'customers' => $customerSales,
            'summary' => [
                'total_customers' => count($customerSales),
                'total_orders' => $salesOrders->count(),
                'total_sales' => $totalSales,
            ],
        ];
    }

    /**
     * Generate Sales by Product Report
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function generateByProduct(Carbon $startDate, Carbon $endDate): array
    {
        $orderItems = SalesOrderItem::whereHas('salesOrder', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('order_date', [
                $startDate->toDateString(),
                $endDate->toDateString()
            ])
                ->whereIn('status', ['confirmed', 'completed']);
        })
            ->with('product')
            ->get();

        $productSales = [];
        $totalQuantity = 0;
        $totalRevenue = 0;

        foreach ($orderItems as $item) {
            $productId = $item->product_id;
            $productName = $item->product->name ?? 'Unknown Product';
            $productCode = $item->product->code ?? '';

            if (!isset($productSales[$productId])) {
                $productSales[$productId] = [
                    'product_id' => $productId,
                    'product_code' => $productCode,
                    'product_name' => $productName,
                    'quantity_sold' => 0,
                    'total_revenue' => 0,
                ];
            }

            $productSales[$productId]['quantity_sold'] += $item->quantity;
            $productSales[$productId]['total_revenue'] += $item->subtotal;
            $totalQuantity += $item->quantity;
            $totalRevenue += $item->subtotal;
        }

        // Sort by total revenue descending
        $productSales = collect($productSales)->sortByDesc('total_revenue')->values()->all();

        return [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'report_type' => 'sales_by_product',
            'currency' => config('app.currency', 'MXN'),
            'products' => $productSales,
            'summary' => [
                'total_products' => count($productSales),
                'total_quantity_sold' => $totalQuantity,
                'total_revenue' => $totalRevenue,
            ],
        ];
    }
}
