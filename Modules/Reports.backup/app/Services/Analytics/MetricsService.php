<?php

namespace Modules\Reports\Services\Analytics;

use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\Customer;
use Modules\Purchase\Models\PurchaseOrder;
use Carbon\Carbon;

class MetricsService
{
    /**
     * Get real-time business metrics
     *
     * @return array
     */
    public function getMetrics(): array
    {
        return [
            'today' => $this->getTodayMetrics(),
            'this_week' => $this->getThisWeekMetrics(),
            'this_month' => $this->getThisMonthMetrics(),
            'this_year' => $this->getThisYearMetrics(),
            'generated_at' => Carbon::now()->toIso8601String(),
        ];
    }

    /**
     * Get today's metrics
     *
     * @return array
     */
    private function getTodayMetrics(): array
    {
        $today = Carbon::today();

        $salesOrders = SalesOrder::whereDate('order_date', $today)
            ->whereIn('status', ['confirmed', 'completed'])
            ->get();

        return [
            'sales_count' => $salesOrders->count(),
            'sales_amount' => $salesOrders->sum('total_amount'),
            'orders_pending' => SalesOrder::whereDate('order_date', $today)
                ->where('status', 'pending')
                ->count(),
            'orders_draft' => SalesOrder::whereDate('order_date', $today)
                ->where('status', 'draft')
                ->count(),
        ];
    }

    /**
     * Get this week's metrics
     *
     * @return array
     */
    private function getThisWeekMetrics(): array
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $salesOrders = SalesOrder::whereBetween('order_date', [
            $startOfWeek->toDateString(),
            $endOfWeek->toDateString()
        ])
            ->whereIn('status', ['confirmed', 'completed'])
            ->get();

        $purchaseOrders = PurchaseOrder::whereBetween('order_date', [
            $startOfWeek->toDateString(),
            $endOfWeek->toDateString()
        ])
            ->whereIn('status', ['approved', 'received'])
            ->get();

        return [
            'sales_count' => $salesOrders->count(),
            'sales_amount' => $salesOrders->sum('total_amount'),
            'purchase_count' => $purchaseOrders->count(),
            'purchase_amount' => $purchaseOrders->sum('total_amount'),
        ];
    }

    /**
     * Get this month's metrics
     *
     * @return array
     */
    private function getThisMonthMetrics(): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $now = Carbon::now();

        $salesOrders = SalesOrder::whereBetween('order_date', [
            $startOfMonth->toDateString(),
            $now->toDateString()
        ])
            ->whereIn('status', ['confirmed', 'completed'])
            ->get();

        $newCustomers = Customer::whereBetween('created_at', [
            $startOfMonth,
            $now
        ])
            ->where('is_active', true)
            ->count();

        $purchaseOrders = PurchaseOrder::whereBetween('order_date', [
            $startOfMonth->toDateString(),
            $now->toDateString()
        ])
            ->whereIn('status', ['approved', 'received'])
            ->get();

        return [
            'sales_count' => $salesOrders->count(),
            'sales_amount' => $salesOrders->sum('total_amount'),
            'average_order_value' => $salesOrders->count() > 0
                ? round($salesOrders->sum('total_amount') / $salesOrders->count(), 2)
                : 0,
            'new_customers' => $newCustomers,
            'purchase_count' => $purchaseOrders->count(),
            'purchase_amount' => $purchaseOrders->sum('total_amount'),
        ];
    }

    /**
     * Get this year's metrics
     *
     * @return array
     */
    private function getThisYearMetrics(): array
    {
        $startOfYear = Carbon::now()->startOfYear();
        $now = Carbon::now();

        $salesOrders = SalesOrder::whereBetween('order_date', [
            $startOfYear->toDateString(),
            $now->toDateString()
        ])
            ->whereIn('status', ['confirmed', 'completed'])
            ->get();

        $purchaseOrders = PurchaseOrder::whereBetween('order_date', [
            $startOfYear->toDateString(),
            $now->toDateString()
        ])
            ->whereIn('status', ['approved', 'received'])
            ->get();

        $totalCustomers = Customer::where('is_active', true)->count();

        return [
            'sales_count' => $salesOrders->count(),
            'sales_amount' => $salesOrders->sum('total_amount'),
            'average_order_value' => $salesOrders->count() > 0
                ? round($salesOrders->sum('total_amount') / $salesOrders->count(), 2)
                : 0,
            'purchase_count' => $purchaseOrders->count(),
            'purchase_amount' => $purchaseOrders->sum('total_amount'),
            'total_customers' => $totalCustomers,
        ];
    }
}
