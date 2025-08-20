<?php

namespace Modules\Purchase\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Contacts\Models\Contact;
use Carbon\Carbon;

class PurchaseOrderController extends Controller
{
    // Actions traits para operaciones CRUD automáticas - JSON:API 5.x
    use Actions\FetchMany;       // GET /api/v1/purchase-orders
    use Actions\FetchOne;        // GET /api/v1/purchase-orders/{id}
    use Actions\Store;           // POST /api/v1/purchase-orders
    use Actions\Update;          // PATCH /api/v1/purchase-orders/{id}
    use Actions\Destroy;         // DELETE /api/v1/purchase-orders/{id}
    
    // Actions para relaciones
    use Actions\FetchRelated;        // GET /api/v1/purchase-orders/{id}/supplier
    use Actions\FetchRelationship;   // GET /api/v1/purchase-orders/{id}/relationships/supplier
    use Actions\UpdateRelationship;  // PATCH /api/v1/purchase-orders/{id}/relationships/supplier
    use Actions\AttachRelationship;  // POST /api/v1/purchase-orders/{id}/relationships/purchase-order-items
    use Actions\DetachRelationship;  // DELETE /api/v1/purchase-orders/{id}/relationships/purchase-order-items

    /**
     * Get purchase reports and analytics
     * GET /api/v1/purchase-orders/reports
     */
    public function reports(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date', Carbon::now()->subMonth());
        $endDate = $request->get('end_date', Carbon::now());

        $query = PurchaseOrder::whereBetween('order_date', [$startDate, $endDate]);

        $reports = [
            'summary' => [
                'total_orders' => (clone $query)->count(),
                'total_amount' => (clone $query)->sum('total_amount'),
                'average_order_value' => (clone $query)->avg('total_amount'),
                'pending_orders' => (clone $query)->where('status', 'pending')->count(),
                'completed_orders' => (clone $query)->where('status', 'received')->count(),
            ],
            'by_status' => (clone $query)
                ->selectRaw('status, COUNT(*) as count, SUM(total_amount) as total_amount')
                ->groupBy('status')
                ->get(),
            'by_supplier' => (clone $query)
                ->join('contacts', 'purchase_orders.contact_id', '=', 'contacts.id')
                ->selectRaw('contacts.name as supplier_name, COUNT(*) as orders_count, SUM(purchase_orders.total_amount) as total_amount')
                ->groupBy('contacts.id', 'contacts.name')
                ->orderBy('total_amount', 'desc')
                ->limit(10)
                ->get(),
            'monthly_trend' => (clone $query)
                ->selectRaw('DATE_FORMAT(order_date, "%Y-%m") as month, COUNT(*) as orders, SUM(total_amount) as amount')
                ->groupBy('month')
                ->orderBy('month')
                ->get(),
        ];

        return response()->json([
            'data' => $reports,
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    /**
     * Get suppliers analytics
     * GET /api/v1/purchase-orders/suppliers
     */
    public function suppliers(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date', Carbon::now()->subMonth());
        $endDate = $request->get('end_date', Carbon::now());

        $suppliers = Contact::where('is_supplier', true)
            ->with(['purchaseOrders' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('order_date', [$startDate, $endDate])
                      ->with('purchaseOrderItems');
            }])
            ->get()
            ->map(function ($supplier) {
                $orders = $supplier->purchaseOrders;
                $totalPurchased = $orders->sum('total_amount');

                return [
                    'id' => $supplier->id,
                    'type' => 'supplier-purchases',
                    'attributes' => [
                        'supplier_name' => $supplier->name,
                        'supplier_email' => $supplier->email,
                        'supplier_classification' => $supplier->classification,
                        'supplier_phone' => $supplier->phone,
                        'total_orders' => $orders->count(),
                        'total_purchased' => round($totalPurchased, 2),
                        'last_order_date' => $orders->max('created_at'),
                        'average_order_value' => $orders->count() > 0 ? round($totalPurchased / $orders->count(), 2) : 0,
                        'orders' => $orders->map(function ($order) {
                            return [
                                'id' => $order->id,
                                'order_date' => $order->order_date,
                                'status' => $order->status,
                                'total_amount' => round($order->total_amount, 2),
                                'items_count' => $order->purchaseOrderItems->count(),
                                'notes' => $order->notes,
                            ];
                        })->toArray()
                    ]
                ];
            })
            ->filter(function ($supplier) {
                return $supplier['attributes']['total_orders'] > 0;
            })
            ->sortByDesc(function ($supplier) {
                return $supplier['attributes']['total_purchased'];
            })
            ->values();

        return response()->json([
            'data' => $suppliers,
            'meta' => [
                'total_suppliers' => $suppliers->count(),
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                'generated_at' => now()->toISOString(),
            ]
        ]);
    }
}
