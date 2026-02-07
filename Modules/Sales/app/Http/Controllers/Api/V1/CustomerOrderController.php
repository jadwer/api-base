<?php

namespace Modules\Sales\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Services\OrderStatusService;
use Modules\Sales\Services\SalesOrderPDFGenerator;
use Modules\Ecommerce\Services\Notifications\OrderNotificationService;
use Modules\Contacts\Models\Contact;

class CustomerOrderController extends Controller
{
    private OrderStatusService $orderStatusService;
    private OrderNotificationService $notificationService;
    private SalesOrderPDFGenerator $pdfGenerator;

    public function __construct(
        OrderStatusService $orderStatusService,
        OrderNotificationService $notificationService,
        SalesOrderPDFGenerator $pdfGenerator
    ) {
        $this->orderStatusService = $orderStatusService;
        $this->notificationService = $notificationService;
        $this->pdfGenerator = $pdfGenerator;
        $this->middleware('auth:sanctum');
    }

    /**
     * Get the contact ID associated with the authenticated user.
     */
    private function getCustomerContactId(): ?int
    {
        $user = auth()->user();
        if (!$user) {
            return null;
        }
        $contact = Contact::where('email', $user->email)
            ->where('is_customer', true)
            ->first();
        return $contact?->id;
    }

    /**
     * List customer's orders
     *
     * GET /api/v1/my-orders
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $contactId = $this->getCustomerContactId();
        if (!$contactId) {
            return response()->json(['data' => [], 'meta' => ['current_page' => 1, 'per_page' => 15, 'total' => 0, 'last_page' => 1]]);
        }

        $query = SalesOrder::where('contact_id', $contactId)
            ->with(['items.product', 'contact'])
            ->orderBy('order_date', 'desc');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by order source
        if ($request->has('source')) {
            $query->where('order_source', $request->source);
        }

        // Date range filter
        if ($request->has('from_date')) {
            $query->whereDate('order_date', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('order_date', '<=', $request->to_date);
        }

        $perPage = min((int) ($request->per_page ?? 15), 100);
        $orders = $query->paginate($perPage);

        return response()->json([
            'data' => $orders->items(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'last_page' => $orders->lastPage(),
            ],
        ]);
    }

    /**
     * Get single order details
     *
     * GET /api/v1/my-orders/{order}
     *
     * @param SalesOrder $order
     * @return JsonResponse
     */
    public function show(SalesOrder $order): JsonResponse
    {
        // Verify order belongs to customer
        $contactId = $this->getCustomerContactId();
        if (!$contactId || $order->contact_id !== $contactId) {
            return response()->json([
                'error' => 'Unauthorized access to order',
            ], 403);
        }

        $order->load(['items.product', 'contact']);

        $statusHistory = $this->orderStatusService->getStatusHistory($order);

        return response()->json([
            'data' => [
                'order' => $order,
                'status_history' => $statusHistory,
                'can_cancel' => $this->canCancelOrder($order),
                'available_actions' => $this->getAvailableActions($order),
            ],
        ]);
    }

    /**
     * Cancel order
     *
     * POST /api/v1/my-orders/{order}/cancel
     *
     * @param Request $request
     * @param SalesOrder $order
     * @return JsonResponse
     */
    public function cancel(Request $request, SalesOrder $order): JsonResponse
    {
        // Verify order belongs to customer
        $contactId = $this->getCustomerContactId();
        if (!$contactId || $order->contact_id !== $contactId) {
            return response()->json([
                'error' => 'Unauthorized access to order',
            ], 403);
        }

        // Check if order can be cancelled
        if (!$this->canCancelOrder($order)) {
            return response()->json([
                'error' => 'Order cannot be cancelled',
                'message' => 'Order is already ' . $order->status,
            ], 400);
        }

        $request->validate([
            'reason' => 'sometimes|string|max:500',
        ]);

        try {
            $reason = $request->reason ?? 'Cancelled by customer';

            $cancelledOrder = $this->orderStatusService->cancelOrder(
                $order,
                $reason
            );

            // Send cancellation email notification
            if ($order->order_source === 'ecommerce') {
                $this->notificationService->sendOrderCancellation($cancelledOrder, $reason);
            }

            return response()->json([
                'data' => $cancelledOrder,
                'message' => 'Order cancelled successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to cancel order',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Request return for order
     *
     * POST /api/v1/my-orders/{order}/return
     *
     * @param Request $request
     * @param SalesOrder $order
     * @return JsonResponse
     */
    public function requestReturn(Request $request, SalesOrder $order): JsonResponse
    {
        // Verify order belongs to customer
        $contactId = $this->getCustomerContactId();
        if (!$contactId || $order->contact_id !== $contactId) {
            return response()->json([
                'error' => 'Unauthorized access to order',
            ], 403);
        }

        // Check if order can be returned (must be delivered)
        if ($order->status !== 'delivered') {
            return response()->json([
                'error' => 'Order cannot be returned',
                'message' => 'Only delivered orders can be returned',
            ], 400);
        }

        $request->validate([
            'reason' => 'required|string|max:500',
            'items' => 'sometimes|array',
        ]);

        try {
            $returnData = [
                'reason' => $request->reason,
                'items' => $request->items ?? [],
            ];

            // Update order metadata with return request
            $order->update([
                'metadata' => array_merge(
                    $order->metadata ?? [],
                    [
                        'return_requested' => true,
                        'return_reason' => $request->reason,
                        'return_items' => $request->items ?? [],
                        'return_requested_at' => now()->toIso8601String(),
                    ]
                ),
            ]);

            // Send return request email notification (to customer and admin)
            if ($order->order_source === 'ecommerce') {
                $this->notificationService->sendReturnRequestConfirmation($order, $returnData);
                $this->notificationService->notifyAdminReturnRequest($order, $returnData);
            }

            return response()->json([
                'data' => $order,
                'message' => 'Return request submitted successfully. Our team will contact you soon.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to submit return request',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Download invoice for order (future implementation)
     *
     * GET /api/v1/my-orders/{order}/invoice
     *
     * @param SalesOrder $order
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function invoice(SalesOrder $order)
    {
        // Verify order belongs to customer
        $contactId = $this->getCustomerContactId();
        if (!$contactId || $order->contact_id !== $contactId) {
            return response()->json([
                'error' => 'Unauthorized access to order',
            ], 403);
        }

        // Generate and download PDF
        return $this->pdfGenerator->download($order);
    }

    /**
     * Check if order can be cancelled
     *
     * @param SalesOrder $order
     * @return bool
     */
    private function canCancelOrder(SalesOrder $order): bool
    {
        // Can cancel if status is pending, confirmed, or processing
        return in_array($order->status, ['pending', 'confirmed', 'processing']);
    }

    /**
     * Get available actions for order
     *
     * @param SalesOrder $order
     * @return array
     */
    private function getAvailableActions(SalesOrder $order): array
    {
        $actions = [];

        if ($this->canCancelOrder($order)) {
            $actions[] = 'cancel';
        }

        if ($order->status === 'delivered') {
            $actions[] = 'return';
            $actions[] = 'review';
        }

        if (in_array($order->status, ['confirmed', 'processing', 'shipped', 'delivered', 'completed'])) {
            $actions[] = 'download_invoice';
            $actions[] = 'track';
        }

        return $actions;
    }
}
