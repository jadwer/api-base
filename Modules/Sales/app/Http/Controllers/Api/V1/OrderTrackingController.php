<?php

namespace Modules\Sales\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Services\OrderStatusService;

class OrderTrackingController extends Controller
{
    private OrderStatusService $orderStatusService;

    public function __construct(OrderStatusService $orderStatusService)
    {
        $this->orderStatusService = $orderStatusService;
        $this->middleware('auth:sanctum');
    }

    /**
     * Get order tracking information
     *
     * GET /api/v1/orders/{order}/tracking
     *
     * @param SalesOrder $order
     * @return JsonResponse
     */
    public function tracking(SalesOrder $order): JsonResponse
    {
        // Verify order belongs to user (unless admin)
        if (!$this->canAccessOrder($order)) {
            return response()->json([
                'error' => 'Unauthorized access to order',
            ], 403);
        }

        $tracking = [
            'order_number' => $order->order_number,
            'status' => $order->status,
            'tracking_number' => $order->tracking_number,
            'tracking_url' => $order->tracking_url,
            'order_date' => $order->order_date,
            'estimated_delivery' => $this->calculateEstimatedDelivery($order),
            'current_location' => $this->getCurrentLocation($order),
            'timeline' => $this->getOrderTimeline($order),
        ];

        return response()->json([
            'data' => $tracking,
        ]);
    }

    /**
     * Get order status history
     *
     * GET /api/v1/orders/{order}/status-history
     *
     * @param SalesOrder $order
     * @return JsonResponse
     */
    public function statusHistory(SalesOrder $order): JsonResponse
    {
        if (!$this->canAccessOrder($order)) {
            return response()->json([
                'error' => 'Unauthorized access to order',
            ], 403);
        }

        $history = $this->orderStatusService->getStatusHistory($order);

        return response()->json([
            'data' => [
                'order_number' => $order->order_number,
                'current_status' => $order->status,
                'history' => $history,
            ],
        ]);
    }

    /**
     * Update order status (admin only)
     *
     * POST /api/v1/orders/{order}/status
     *
     * @param Request $request
     * @param SalesOrder $order
     * @return JsonResponse
     */
    public function updateStatus(Request $request, SalesOrder $order): JsonResponse
    {
        // Check if user has permission (admin/god)
        if (!auth()->user()->hasRole(['god', 'admin'])) {
            return response()->json([
                'error' => 'Insufficient permissions',
            ], 403);
        }

        $request->validate([
            'status' => 'required|string|in:pending,confirmed,processing,shipped,delivered,completed,cancelled,returned,refunded',
            'notes' => 'sometimes|string|max:500',
            'tracking_number' => 'sometimes|string',
            'tracking_url' => 'sometimes|url',
        ]);

        try {
            $updatedOrder = $this->orderStatusService->updateStatus(
                $order,
                $request->status,
                $request->notes,
                $request->only(['tracking_number', 'tracking_url'])
            );

            return response()->json([
                'data' => $updatedOrder,
                'message' => 'Order status updated successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update order status',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Mark order as shipped (admin only)
     *
     * POST /api/v1/orders/{order}/ship
     *
     * @param Request $request
     * @param SalesOrder $order
     * @return JsonResponse
     */
    public function ship(Request $request, SalesOrder $order): JsonResponse
    {
        if (!auth()->user()->hasRole(['god', 'admin'])) {
            return response()->json([
                'error' => 'Insufficient permissions',
            ], 403);
        }

        $request->validate([
            'tracking_number' => 'sometimes|string',
            'tracking_url' => 'sometimes|url',
            'carrier' => 'sometimes|string',
        ]);

        try {
            $updatedOrder = $this->orderStatusService->markAsShipped(
                $order,
                $request->tracking_number,
                $request->tracking_url,
                $request->carrier
            );

            return response()->json([
                'data' => $updatedOrder,
                'message' => 'Order marked as shipped successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to mark order as shipped',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Check if user can access order
     *
     * @param SalesOrder $order
     * @return bool
     */
    private function canAccessOrder(SalesOrder $order): bool
    {
        $user = auth()->user();

        // Admin can access all orders
        if ($user->hasRole(['god', 'admin'])) {
            return true;
        }

        // Customer can only access their own orders
        return $order->customer_id === $user->id;
    }

    /**
     * Calculate estimated delivery date
     *
     * @param SalesOrder $order
     * @return string|null
     */
    private function calculateEstimatedDelivery(SalesOrder $order): ?string
    {
        if ($order->status === 'delivered' || $order->status === 'completed') {
            $deliveredAt = $order->metadata['delivered_at'] ?? null;
            return $deliveredAt ? \Carbon\Carbon::parse($deliveredAt)->toDateString() : null;
        }

        // If shipped, estimate based on carrier
        if ($order->status === 'shipped') {
            // Default: 3-5 business days from ship date
            $shippedDate = $order->metadata['shipped_at'] ?? now();
            return \Carbon\Carbon::parse($shippedDate)->addBusinessDays(4)->toDateString();
        }

        return null;
    }

    /**
     * Get current location (mock)
     *
     * @param SalesOrder $order
     * @return string|null
     */
    private function getCurrentLocation(SalesOrder $order): ?string
    {
        if ($order->status === 'delivered') {
            return 'Delivered to customer';
        }

        if ($order->status === 'shipped') {
            return 'In transit';
        }

        if ($order->status === 'processing') {
            return 'Warehouse - Preparing for shipment';
        }

        return null;
    }

    /**
     * Get order timeline
     *
     * @param SalesOrder $order
     * @return array
     */
    private function getOrderTimeline(SalesOrder $order): array
    {
        $timeline = [];

        // Order placed
        $timeline[] = [
            'status' => 'placed',
            'label' => 'Order Placed',
            'timestamp' => $order->order_date,
            'completed' => true,
        ];

        // Confirmed
        $timeline[] = [
            'status' => 'confirmed',
            'label' => 'Order Confirmed',
            'timestamp' => $order->created_at,
            'completed' => in_array($order->status, ['confirmed', 'processing', 'shipped', 'delivered', 'completed']),
        ];

        // Processing
        $timeline[] = [
            'status' => 'processing',
            'label' => 'Processing',
            'timestamp' => null,
            'completed' => in_array($order->status, ['processing', 'shipped', 'delivered', 'completed']),
        ];

        // Shipped
        $shippedAt = $order->metadata['shipped_at'] ?? null;
        $timeline[] = [
            'status' => 'shipped',
            'label' => 'Shipped',
            'timestamp' => $shippedAt,
            'completed' => in_array($order->status, ['shipped', 'delivered', 'completed']),
        ];

        // Delivered
        $deliveredAt = $order->metadata['delivered_at'] ?? null;
        $timeline[] = [
            'status' => 'delivered',
            'label' => 'Delivered',
            'timestamp' => $deliveredAt,
            'completed' => in_array($order->status, ['delivered', 'completed']),
        ];

        return $timeline;
    }
}
