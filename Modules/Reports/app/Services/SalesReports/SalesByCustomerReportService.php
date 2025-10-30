<?php

namespace Modules\Reports\Services\SalesReports;

use Carbon\Carbon;
use Modules\Sales\Models\SalesOrder;
use Illuminate\Support\Facades\DB;

class SalesByCustomerReportService
{
    /**
     * Generate Sales by Customer Report
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string $currency
     * @return array
     */
    public function generate(Carbon $startDate, Carbon $endDate, string $currency = 'MXN'): array
    {
        $sales = SalesOrder::whereBetween('order_date', [$startDate, $endDate])
            ->whereIn('status', ['completed', 'delivered'])
            ->with('customer')
            ->get();

        $salesByCustomer = $sales->groupBy('customer_id')->map(function ($customerOrders) {
            $customer = $customerOrders->first()->customer;

            return [
                'customer_id' => $customer->id ?? null,
                'customer_name' => $customer->name ?? 'Unknown',
                'total_orders' => $customerOrders->count(),
                'total_amount' => round($customerOrders->sum('total_amount'), 2),
                'orders' => $customerOrders->map(function ($order) {
                    return [
                        'order_number' => $order->order_number,
                        'order_date' => $order->order_date,
                        'total_amount' => round($order->total_amount, 2),
                        'status' => $order->status,
                    ];
                })->toArray(),
            ];
        })->sortByDesc('total_amount')->values();

        $totalSales = $salesByCustomer->sum('total_amount');
        $totalOrders = $salesByCustomer->sum('total_orders');

        return [
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
            'currency' => $currency,
            'salesByCustomer' => $salesByCustomer->toArray(),
            'summary' => [
                'total_customers' => $salesByCustomer->count(),
                'total_orders' => $totalOrders,
                'total_sales' => round($totalSales, 2),
                'average_per_customer' => $salesByCustomer->count() > 0 
                    ? round($totalSales / $salesByCustomer->count(), 2) 
                    : 0,
            ],
            'generatedAt' => now()->toIso8601String(),
        ];
    }
}
