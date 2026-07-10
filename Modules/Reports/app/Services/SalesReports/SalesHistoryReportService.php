<?php

namespace Modules\Reports\Services\SalesReports;

use Modules\Sales\Models\SalesOrder;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Historico de Ventas (v1)
 *
 * Reporte de trabajo diario estilo Bind ERP (ver auditoria de migracion, doc 07, seccion 3.1).
 * Una fila por orden de venta con costo, utilidad, subtotal, descuento, IVA, total y estatus.
 *
 * v1 cubre filtros sobre campos existentes. Pagos/NC/Saldo (join AR) y filtros de
 * campos aun inexistentes (lista de precio, sucursal, como se entero) quedan para v2.
 */
class SalesHistoryReportService
{
    public const GROUP_OPTIONS = ['none', 'customer', 'salesperson', 'status', 'day', 'month'];

    public const STATUSES = [
        'draft', 'pending', 'confirmed', 'processing', 'shipped',
        'delivered', 'completed', 'cancelled', 'returned', 'refunded',
    ];

    private SalesAdvancedReportService $advancedReportService;

    public function __construct(SalesAdvancedReportService $advancedReportService)
    {
        $this->advancedReportService = $advancedReportService;
    }

    /**
     * Generate the sales history report.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param array $filters Supported keys: contact_id, assigned_to, product_id,
     *                       category_id, brand_id, statuses (array), currency,
     *                       iva (bool|null), order_number
     * @param string $groupBy One of GROUP_OPTIONS
     * @param int|null $pageNumber Null disables pagination (used by exports)
     * @param int|null $pageSize
     * @return array
     */
    public function generate(
        Carbon $startDate,
        Carbon $endDate,
        array $filters = [],
        string $groupBy = 'none',
        ?int $pageNumber = null,
        ?int $pageSize = null
    ): array {
        $orders = $this->buildQuery($startDate, $endDate, $filters)->get();

        $rows = $orders->map(fn (SalesOrder $order) => $this->buildRow($order))->values();

        $report = [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'report_type' => 'sales_history',
            'currency' => config('app.currency', 'MXN'),
            'group_by' => $groupBy,
            'data' => $rows->all(),
            // Totals are ALWAYS computed over the full filtered set (never per page)
            'totals' => $this->aggregate($rows),
        ];

        if ($groupBy !== 'none') {
            $report['grouped'] = $this->groupRows($rows, $groupBy);
        }

        if ($pageNumber !== null && $pageSize !== null) {
            $report['data'] = $rows->forPage($pageNumber, $pageSize)->values()->all();
            $report['meta'] = [
                'page' => [
                    'number' => $pageNumber,
                    'size' => $pageSize,
                    'total' => $rows->count(),
                    'last_page' => (int) max(1, (int) ceil($rows->count() / $pageSize)),
                ],
            ];
        }

        return $report;
    }

    /**
     * Build the filtered sales orders query.
     */
    private function buildQuery(Carbon $startDate, Carbon $endDate, array $filters): Builder
    {
        // Bounds as datetime: order_date can be stored as 'Y-m-d' o 'Y-m-d 00:00:00'
        // (cast date sobre SQLite); endOfDay garantiza incluir la fecha final.
        $query = SalesOrder::query()
            ->with(['contact', 'assignedUser', 'items.product'])
            ->whereBetween('order_date', [
                $startDate->copy()->startOfDay(),
                $endDate->copy()->endOfDay(),
            ])
            ->orderBy('order_date')
            ->orderBy('id');

        if (!empty($filters['contact_id'])) {
            $query->where('contact_id', $filters['contact_id']);
        }

        if (!empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        if (!empty($filters['product_id'])) {
            $query->whereHas('items', fn ($q) => $q->where('product_id', $filters['product_id']));
        }

        if (!empty($filters['category_id'])) {
            $query->whereHas('items.product', fn ($q) => $q->where('category_id', $filters['category_id']));
        }

        if (!empty($filters['brand_id'])) {
            $query->whereHas('items.product', fn ($q) => $q->where('brand_id', $filters['brand_id']));
        }

        if (!empty($filters['statuses'])) {
            $query->whereIn('status', $filters['statuses']);
        }

        if (!empty($filters['currency'])) {
            $query->where('currency', strtoupper($filters['currency']));
        }

        if (array_key_exists('iva', $filters) && $filters['iva'] !== null) {
            $query->whereHas('items.product', fn ($q) => $q->where('iva', (bool) $filters['iva']));
        }

        if (!empty($filters['order_number'])) {
            $query->where('order_number', 'like', '%' . $filters['order_number'] . '%');
        }

        return $query;
    }

    /**
     * Build a report row for a single order.
     *
     * Cost is resolved via SalesAdvancedReportService::calculateOrderCost
     * (product cost, with 70% of unit price as fallback estimate).
     */
    private function buildRow(SalesOrder $order): array
    {
        $cost = round($this->advancedReportService->calculateOrderCost($order), 2);
        $subtotal = round((float) $order->subtotal, 2);

        return [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'date' => $order->order_date?->toDateString(),
            'customer_id' => $order->contact_id,
            'customer_name' => $order->contact?->name ?? 'Sin cliente',
            'salesperson_id' => $order->assigned_to,
            'salesperson_name' => $order->assignedUser?->name ?? 'Sin asignar',
            'cost' => $cost,
            'profit' => round($subtotal - $cost, 2),
            'subtotal' => $subtotal,
            'discount' => round((float) $order->discount_total, 2),
            'iva' => round((float) $order->tax_amount, 2),
            'total' => round((float) $order->total_amount, 2),
            'status' => $order->status,
        ];
    }

    /**
     * Aggregate money columns over a set of rows.
     */
    private function aggregate(Collection $rows): array
    {
        return [
            'cost' => round($rows->sum('cost'), 2),
            'profit' => round($rows->sum('profit'), 2),
            'subtotal' => round($rows->sum('subtotal'), 2),
            'discount' => round($rows->sum('discount'), 2),
            'iva' => round($rows->sum('iva'), 2),
            'total' => round($rows->sum('total'), 2),
            'count' => $rows->count(),
        ];
    }

    /**
     * Group rows and aggregate each group.
     */
    private function groupRows(Collection $rows, string $groupBy): array
    {
        $keyed = $rows->map(function (array $row) use ($groupBy) {
            [$key, $label] = match ($groupBy) {
                'customer' => [(string) ($row['customer_id'] ?? 'none'), $row['customer_name']],
                'salesperson' => [(string) ($row['salesperson_id'] ?? 'unassigned'), $row['salesperson_name']],
                'status' => [$row['status'], $row['status']],
                'day' => [(string) $row['date'], (string) $row['date']],
                'month' => [substr((string) $row['date'], 0, 7), substr((string) $row['date'], 0, 7)],
            };

            $row['_group_key'] = $key;
            $row['_group_label'] = $label;

            return $row;
        });

        $grouped = $keyed
            ->groupBy('_group_key')
            ->map(function (Collection $groupRows, $key) {
                return array_merge(
                    [
                        'group_key' => (string) $key,
                        'group_label' => (string) $groupRows->first()['_group_label'],
                    ],
                    $this->aggregate($groupRows)
                );
            })
            ->values();

        // Time-based groups read chronologically; the rest by total descending
        $grouped = in_array($groupBy, ['day', 'month'], true)
            ? $grouped->sortBy('group_key')
            : $grouped->sortByDesc('total');

        return $grouped->values()->all();
    }
}
