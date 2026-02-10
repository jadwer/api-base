<?php

namespace Modules\Sales\Http\Controllers\Api\V1;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Services\SalesOrderPDFGenerator;
use Modules\Contacts\Models\Contact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\Support\DatabaseCompatibilityHelper as DBHelper;

class SalesOrderController extends Controller
{
    use Actions\FetchMany;
    use Actions\FetchOne;
    use Actions\Store;
    use Actions\Update;
    use Actions\Destroy;
    use Actions\FetchRelated;
    use Actions\FetchRelationship;
    use Actions\UpdateRelationship;
    use Actions\AttachRelationship;
    use Actions\DetachRelationship;

    /**
     * Get sales reports and analytics
     */
    public function reports(Request $request): JsonResponse
    {
        if (Gate::denies('sales-orders.index')) {
            abort(403, 'No tiene permisos para ver reportes de ventas');
        }

        $period = min((int) $request->get('period', '30'), 365); // days, max 1 year
        $startDate = now()->subDays($period);

        // Basic sales metrics
        $totalOrders = SalesOrder::where('created_at', '>=', $startDate)->count();
        $totalRevenue = DB::table('sales_orders')
            ->join('sales_order_items', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')
            ->where('sales_orders.created_at', '>=', $startDate)
            ->sum('sales_order_items.total');

        // Sales by status
        $salesByStatus = SalesOrder::where('created_at', '>=', $startDate)
            ->select('status', DB::raw('count(*) as count'), DB::raw('sum((select sum(total) from sales_order_items where sales_order_id = sales_orders.id)) as revenue'))
            ->groupBy('status')
            ->get();

        // Top customers by revenue
        $topCustomers = DB::table('sales_orders')
            ->join('contacts', 'sales_orders.contact_id', '=', 'contacts.id')
            ->join('sales_order_items', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')
            ->where('sales_orders.created_at', '>=', $startDate)
            ->select(
                'contacts.id',
                'contacts.name',
                'contacts.email',
                DB::raw('count(distinct sales_orders.id) as total_orders'),
                DB::raw('sum(sales_order_items.total) as total_revenue')
            )
            ->groupBy('contacts.id', 'contacts.name', 'contacts.email')
            ->orderBy('total_revenue', 'desc')
            ->limit(10)
            ->get();

        // Sales trend (daily for last 30 days)
        $salesTrend = DB::table('sales_orders')
            ->join('sales_order_items', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')
            ->where('sales_orders.created_at', '>=', $startDate)
            ->select(
                DB::raw(DBHelper::dateOnly('sales_orders.created_at') . ' as date'),
                DB::raw('count(distinct sales_orders.id) as orders'),
                DB::raw('sum(sales_order_items.total) as revenue')
            )
            ->groupBy(DB::raw(DBHelper::dateOnly('sales_orders.created_at')))
            ->orderBy('date')
            ->get();

        // Average order value
        $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        return response()->json([
            'data' => [
                'type' => 'sales-reports',
                'id' => 'current',
                'attributes' => [
                    'period_days' => (int)$period,
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => now()->format('Y-m-d'),
                    'summary' => [
                        'total_orders' => $totalOrders,
                        'total_revenue' => round($totalRevenue, 2),
                        'average_order_value' => round($avgOrderValue, 2),
                    ],
                    'sales_by_status' => $salesByStatus,
                    'top_customers' => $topCustomers,
                    'sales_trend' => $salesTrend,
                    'generated_at' => now()->toISOString(),
                ]
            ]
        ]);
    }

    /**
     * Get sales orders grouped by customers
     */
    public function customers(Request $request): JsonResponse
    {
        if (Gate::denies('sales-orders.index')) {
            abort(403, 'No tiene permisos para ver datos de clientes');
        }

        $period = min((int) $request->get('period', '90'), 365); // days, max 1 year
        $startDate = now()->subDays($period);
        $perPage = min((int) ($request->get('per_page', 25)), 100);

        $customers = Contact::where('is_customer', true)
            ->whereHas('salesOrders', function ($query) use ($startDate) {
                $query->where('created_at', '>=', $startDate);
            })
            ->with(['salesOrders' => function ($query) use ($startDate) {
                $query->where('created_at', '>=', $startDate)
                      ->with('items');
            }])
            ->paginate($perPage);

        $transformed = collect($customers->items())->map(function ($customer) {
            $orders = $customer->salesOrders;
            $totalRevenue = $orders->sum(function ($order) {
                return $order->items->sum('total');
            });

            return [
                'id' => $customer->id,
                'type' => 'customer-sales',
                'attributes' => [
                    'customer_name' => $customer->name,
                    'customer_email' => $customer->email,
                    'customer_classification' => $customer->classification,
                    'total_orders' => $orders->count(),
                    'total_revenue' => round($totalRevenue, 2),
                    'last_order_date' => $orders->max('created_at'),
                    'average_order_value' => $orders->count() > 0 ? round($totalRevenue / $orders->count(), 2) : 0,
                    'orders' => $orders->map(function ($order) {
                        $orderTotal = $order->items->sum('total');

                        return [
                            'id' => $order->id,
                            'order_number' => $order->order_number,
                            'status' => $order->status,
                            'order_date' => $order->order_date,
                            'total_amount' => round($orderTotal, 2),
                            'items_count' => $order->items->count(),
                        ];
                    })->toArray()
                ]
            ];
        })->values();

        return response()->json([
            'data' => $transformed,
            'meta' => [
                'current_page' => $customers->currentPage(),
                'per_page' => $customers->perPage(),
                'total_customers' => $customers->total(),
                'last_page' => $customers->lastPage(),
                'period_days' => $period,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d'),
                'generated_at' => now()->toISOString(),
            ]
        ]);
    }

    /**
     * Cancel a sales order
     * POST /api/v1/sales-orders/{salesOrder}/cancel
     */
    public function cancel(SalesOrder $salesOrder): JsonResponse
    {
        $user = request()->user('sanctum');
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        if (!$user->can('sales-orders.update') && !$user->hasAnyRole(['god', 'admin'])) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        if ($salesOrder->status === 'cancelled') {
            return response()->json(['error' => 'La orden ya esta cancelada'], 400);
        }

        if ($salesOrder->status === 'completed') {
            return response()->json(['error' => 'No se puede cancelar una orden completada'], 400);
        }

        $salesOrder->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Orden cancelada exitosamente',
            'data' => [
                'type' => 'sales-orders',
                'id' => (string) $salesOrder->id,
                'attributes' => [
                    'status' => 'cancelled',
                    'orderNumber' => $salesOrder->order_number,
                ],
            ],
        ]);
    }

    // ==========================================================
    // SA-M011: PDF Generation Methods
    // ==========================================================

    /**
     * Generate and return PDF info for sales order
     */
    public function generatePdf(SalesOrder $salesOrder, SalesOrderPDFGenerator $generator): JsonResponse
    {
        if (Gate::denies('sales-orders.show')) {
            abort(403, 'No tiene permisos para ver esta orden');
        }

        try {
            $path = $generator->generate($salesOrder);
            $url = asset('storage/' . $path);

            return response()->json([
                'message' => 'PDF generado correctamente',
                'data' => [
                    'id' => $salesOrder->id,
                    'order_number' => $salesOrder->order_number,
                    'pdf_path' => $path,
                    'pdf_url' => $url,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al generar PDF',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download PDF for sales order
     */
    public function downloadPdf(SalesOrder $salesOrder, SalesOrderPDFGenerator $generator)
    {
        if (Gate::denies('sales-orders.show')) {
            abort(403, 'No tiene permisos para descargar esta orden');
        }

        try {
            return $generator->download($salesOrder);
        } catch (\Exception $e) {
            abort(500, 'Error al descargar PDF: ' . $e->getMessage());
        }
    }

    /**
     * Preview PDF inline for sales order
     */
    public function previewPdf(SalesOrder $salesOrder, SalesOrderPDFGenerator $generator)
    {
        if (Gate::denies('sales-orders.show')) {
            abort(403, 'No tiene permisos para ver esta orden');
        }

        try {
            return $generator->preview($salesOrder);
        } catch (\Exception $e) {
            abort(500, 'Error al previsualizar PDF: ' . $e->getMessage());
        }
    }

    /**
     * Stream PDF without storing for sales order
     */
    public function streamPdf(SalesOrder $salesOrder, Request $request, SalesOrderPDFGenerator $generator)
    {
        if (Gate::denies('sales-orders.show')) {
            abort(403, 'No tiene permisos para ver esta orden');
        }

        $options = [
            'show_bank_accounts' => $request->boolean('show_bank_accounts', true),
            'show_conditions' => $request->boolean('show_conditions', true),
        ];

        try {
            return $generator->stream($salesOrder, $options);
        } catch (\Exception $e) {
            abort(500, 'Error al generar PDF: ' . $e->getMessage());
        }
    }
}
