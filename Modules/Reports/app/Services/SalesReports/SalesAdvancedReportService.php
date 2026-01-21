<?php

namespace Modules\Reports\Services\SalesReports;

use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\ProductBatch;
use Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Phase 13: Advanced Sales Reports Service
 *
 * Provides reports with:
 * - Sales by Employee/Salesperson
 * - Sales by Batch (lot traceability)
 * - Profitability metrics (cost, margin, profit)
 */
class SalesAdvancedReportService
{
    /**
     * Generate Sales by Employee/Salesperson Report
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param int|null $employeeId Optional filter by specific employee
     * @return array
     */
    public function generateByEmployee(Carbon $startDate, Carbon $endDate, ?int $employeeId = null): array
    {
        $query = SalesOrder::whereBetween('order_date', [
            $startDate->toDateString(),
            $endDate->toDateString()
        ])
            ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
            ->with('assignedUser');

        if ($employeeId) {
            $query->where('assigned_to', $employeeId);
        }

        $salesOrders = $query->get();

        $employeeSales = [];
        $totalSales = 0;
        $totalOrders = 0;
        $totalCost = 0;

        foreach ($salesOrders as $order) {
            $employeeId = $order->assigned_to;
            $employeeName = $order->assignedUser?->name ?? 'Sin asignar';

            $key = $employeeId ?? 'unassigned';

            if (!isset($employeeSales[$key])) {
                $employeeSales[$key] = [
                    'employee_id' => $employeeId,
                    'employee_name' => $employeeName,
                    'order_count' => 0,
                    'total_sales' => 0,
                    'total_cost' => 0,
                    'gross_profit' => 0,
                    'margin_percentage' => 0,
                    'average_order_value' => 0,
                ];
            }

            // Calculate order cost from items
            $orderCost = $this->calculateOrderCost($order);

            $employeeSales[$key]['order_count']++;
            $employeeSales[$key]['total_sales'] += $order->total_amount;
            $employeeSales[$key]['total_cost'] += $orderCost;
            $totalSales += $order->total_amount;
            $totalCost += $orderCost;
            $totalOrders++;
        }

        // Calculate profits and margins
        foreach ($employeeSales as &$employee) {
            $employee['gross_profit'] = $employee['total_sales'] - $employee['total_cost'];
            $employee['margin_percentage'] = $employee['total_sales'] > 0
                ? round(($employee['gross_profit'] / $employee['total_sales']) * 100, 2)
                : 0;
            $employee['average_order_value'] = $employee['order_count'] > 0
                ? round($employee['total_sales'] / $employee['order_count'], 2)
                : 0;
        }

        // Sort by total sales descending
        $employeeSales = collect($employeeSales)->sortByDesc('total_sales')->values()->all();

        return [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'report_type' => 'sales_by_employee',
            'currency' => config('app.currency', 'MXN'),
            'employees' => $employeeSales,
            'summary' => [
                'total_employees' => count($employeeSales),
                'total_orders' => $totalOrders,
                'total_sales' => round($totalSales, 2),
                'total_cost' => round($totalCost, 2),
                'total_profit' => round($totalSales - $totalCost, 2),
                'average_margin' => $totalSales > 0
                    ? round((($totalSales - $totalCost) / $totalSales) * 100, 2)
                    : 0,
            ],
        ];
    }

    /**
     * Generate Sales by Batch/Lot Report
     *
     * Tracks which batches were sold in a period (traceability)
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param int|null $productId Optional filter by product
     * @param string|null $batchNumber Optional filter by batch number
     * @return array
     */
    public function generateByBatch(Carbon $startDate, Carbon $endDate, ?int $productId = null, ?string $batchNumber = null): array
    {
        // Get inventory movements of type 'exit' with reference_type 'sale' (or sale returns)
        // InventoryMovement types are: entry, exit, transfer, adjustment
        // For sales traceability, we look at 'exit' movements with reference_type 'sale'
        $query = InventoryMovement::where('movement_type', 'exit')
            ->whereIn('reference_type', ['sale', 'sale_return', 'shipment'])
            ->whereBetween('movement_date', [
                $startDate->toDateString(),
                $endDate->toDateString()
            ])
            ->whereNotNull('product_batch_id')
            ->with(['productBatch', 'product']);

        if ($productId) {
            $query->where('product_id', $productId);
        }

        if ($batchNumber) {
            $query->whereHas('productBatch', function ($q) use ($batchNumber) {
                $q->where('batch_number', 'like', "%{$batchNumber}%");
            });
        }

        $movements = $query->get();

        $batchSales = [];
        $totalQuantity = 0;
        $totalRevenue = 0;
        $totalCost = 0;

        foreach ($movements as $movement) {
            $batch = $movement->productBatch;
            if (!$batch) continue;

            $batchKey = $batch->id;

            if (!isset($batchSales[$batchKey])) {
                $batchSales[$batchKey] = [
                    'batch_id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'lot_number' => $batch->lot_number,
                    'product_id' => $movement->product_id,
                    'product_name' => $movement->product?->name ?? 'Producto desconocido',
                    'product_sku' => $movement->product?->sku ?? '',
                    'expiration_date' => $batch->expiration_date?->toDateString(),
                    'is_expiring_soon' => $batch->isExpiringSoon(30),
                    'quantity_sold' => 0,
                    'unit_cost' => $batch->unit_cost ?? 0,
                    'total_cost' => 0,
                    'estimated_revenue' => 0,
                    'movement_count' => 0,
                ];
            }

            $qty = abs($movement->quantity);
            $cost = $movement->unit_cost ?? $batch->unit_cost ?? 0;
            $revenue = $movement->total_value ?? ($qty * ($movement->product?->price ?? 0));

            $batchSales[$batchKey]['quantity_sold'] += $qty;
            $batchSales[$batchKey]['total_cost'] += ($qty * $cost);
            $batchSales[$batchKey]['estimated_revenue'] += $revenue;
            $batchSales[$batchKey]['movement_count']++;

            $totalQuantity += $qty;
            $totalCost += ($qty * $cost);
            $totalRevenue += $revenue;
        }

        // Calculate profit for each batch
        foreach ($batchSales as &$batch) {
            $batch['gross_profit'] = $batch['estimated_revenue'] - $batch['total_cost'];
            $batch['margin_percentage'] = $batch['estimated_revenue'] > 0
                ? round(($batch['gross_profit'] / $batch['estimated_revenue']) * 100, 2)
                : 0;
        }

        // Sort by quantity sold descending
        $batchSales = collect($batchSales)->sortByDesc('quantity_sold')->values()->all();

        return [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'report_type' => 'sales_by_batch',
            'currency' => config('app.currency', 'MXN'),
            'batches' => $batchSales,
            'summary' => [
                'total_batches' => count($batchSales),
                'total_quantity_sold' => round($totalQuantity, 2),
                'total_cost' => round($totalCost, 2),
                'total_revenue' => round($totalRevenue, 2),
                'total_profit' => round($totalRevenue - $totalCost, 2),
                'average_margin' => $totalRevenue > 0
                    ? round((($totalRevenue - $totalCost) / $totalRevenue) * 100, 2)
                    : 0,
            ],
        ];
    }

    /**
     * Generate Sales with Profitability Metrics
     *
     * Enhanced version of sales-by-product with cost, profit, margin
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param int|null $categoryId Optional filter by category
     * @return array
     */
    public function generateProfitabilityReport(Carbon $startDate, Carbon $endDate, ?int $categoryId = null): array
    {
        $query = SalesOrderItem::whereHas('salesOrder', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('order_date', [
                $startDate->toDateString(),
                $endDate->toDateString()
            ])
            ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered']);
        })
        ->with(['product', 'product.category']);

        if ($categoryId) {
            $query->whereHas('product', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }

        $items = $query->get();

        $productStats = [];
        $totalRevenue = 0;
        $totalCost = 0;
        $totalQuantity = 0;

        foreach ($items as $item) {
            $productId = $item->product_id;
            $product = $item->product;

            if (!isset($productStats[$productId])) {
                $productStats[$productId] = [
                    'product_id' => $productId,
                    'product_code' => $product->code ?? $product->sku ?? '',
                    'product_name' => $product->name ?? 'Producto desconocido',
                    'category_name' => $product->category?->name ?? 'Sin categoría',
                    'quantity_sold' => 0,
                    'revenue' => 0,
                    'cost' => 0,
                    'gross_profit' => 0,
                    'margin_percentage' => 0,
                    'average_price' => 0,
                    'average_cost' => 0,
                ];
            }

            $qty = $item->quantity;
            $revenue = $item->total ?? ($qty * $item->unit_price);
            $unitCost = $product->cost ?? ($item->unit_price * 0.7); // Estimate if no cost
            $cost = $qty * $unitCost;

            $productStats[$productId]['quantity_sold'] += $qty;
            $productStats[$productId]['revenue'] += $revenue;
            $productStats[$productId]['cost'] += $cost;

            $totalQuantity += $qty;
            $totalRevenue += $revenue;
            $totalCost += $cost;
        }

        // Calculate derived metrics
        foreach ($productStats as &$stat) {
            $stat['gross_profit'] = $stat['revenue'] - $stat['cost'];
            $stat['margin_percentage'] = $stat['revenue'] > 0
                ? round(($stat['gross_profit'] / $stat['revenue']) * 100, 2)
                : 0;
            $stat['average_price'] = $stat['quantity_sold'] > 0
                ? round($stat['revenue'] / $stat['quantity_sold'], 2)
                : 0;
            $stat['average_cost'] = $stat['quantity_sold'] > 0
                ? round($stat['cost'] / $stat['quantity_sold'], 2)
                : 0;
        }

        // Sort by revenue descending
        $productStats = collect($productStats)->sortByDesc('revenue')->values()->all();

        return [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'report_type' => 'sales_profitability',
            'currency' => config('app.currency', 'MXN'),
            'products' => $productStats,
            'summary' => [
                'total_products' => count($productStats),
                'total_quantity' => round($totalQuantity, 2),
                'total_revenue' => round($totalRevenue, 2),
                'total_cost' => round($totalCost, 2),
                'total_profit' => round($totalRevenue - $totalCost, 2),
                'average_margin' => $totalRevenue > 0
                    ? round((($totalRevenue - $totalCost) / $totalRevenue) * 100, 2)
                    : 0,
            ],
        ];
    }

    /**
     * Generate Daily/Weekly/Monthly Sales Trend
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string $groupBy 'day', 'week', 'month'
     * @return array
     */
    public function generateSalesTrend(Carbon $startDate, Carbon $endDate, string $groupBy = 'day'): array
    {
        $driver = DB::getDriverName();
        $isSqlite = $driver === 'sqlite';

        // Use database-agnostic date functions
        $dateFormat = match ($groupBy) {
            'week' => $isSqlite ? "strftime('%Y-%W', order_date)" : 'YEARWEEK(order_date, 1)',
            'month' => $isSqlite ? "strftime('%Y-%m', order_date)" : 'DATE_FORMAT(order_date, "%Y-%m")',
            default => $isSqlite ? "date(order_date)" : 'DATE(order_date)',
        };

        $labelSelect = match ($groupBy) {
            'week' => $isSqlite
                ? "strftime('%Y-W%W', MIN(order_date)) as period_label"
                : "DATE_FORMAT(MIN(order_date), '%Y-W%v') as period_label",
            'month' => $isSqlite
                ? "strftime('%Y-%m', MIN(order_date)) as period_label"
                : "DATE_FORMAT(MIN(order_date), '%Y-%m') as period_label",
            default => $isSqlite
                ? "date(MIN(order_date)) as period_label"
                : "DATE_FORMAT(MIN(order_date), '%Y-%m-%d') as period_label",
        };

        $trends = SalesOrder::whereBetween('order_date', [
            $startDate->toDateString(),
            $endDate->toDateString()
        ])
            ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
            ->selectRaw("
                {$dateFormat} as period_key,
                {$labelSelect},
                COUNT(*) as order_count,
                SUM(total_amount) as total_sales,
                SUM(subtotal) as subtotal,
                SUM(tax_amount) as tax_total,
                AVG(total_amount) as average_order_value
            ")
            ->groupByRaw($dateFormat)
            ->orderByRaw("MIN(order_date)")
            ->get();

        $trendData = $trends->map(function ($row) {
            return [
                'period' => $row->period_label,
                'order_count' => (int) $row->order_count,
                'total_sales' => round((float) $row->total_sales, 2),
                'subtotal' => round((float) $row->subtotal, 2),
                'tax_total' => round((float) $row->tax_total, 2),
                'average_order_value' => round((float) $row->average_order_value, 2),
            ];
        })->toArray();

        $totalSales = collect($trendData)->sum('total_sales');
        $totalOrders = collect($trendData)->sum('order_count');

        return [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'report_type' => 'sales_trend',
            'group_by' => $groupBy,
            'currency' => config('app.currency', 'MXN'),
            'trends' => $trendData,
            'summary' => [
                'total_periods' => count($trendData),
                'total_orders' => $totalOrders,
                'total_sales' => round($totalSales, 2),
                'average_per_period' => count($trendData) > 0
                    ? round($totalSales / count($trendData), 2)
                    : 0,
            ],
        ];
    }

    /**
     * Calculate order cost from items
     */
    private function calculateOrderCost(SalesOrder $order): float
    {
        $cost = 0;
        foreach ($order->items as $item) {
            $unitCost = $item->product?->cost ?? ($item->unit_price * 0.7);
            $cost += $item->quantity * $unitCost;
        }
        return $cost;
    }
}
