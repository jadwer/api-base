<?php

namespace Modules\Sales\Services;

use Modules\Sales\Models\SalesOrder;
use Modules\Ecommerce\Services\Notifications\OrderNotificationService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OrderStatusService
{
    private OrderNotificationService $notificationService;

    public function __construct(OrderNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Valid status transitions
     *
     * @var array
     */
    private array $validTransitions = [
        'draft' => ['pending', 'cancelled'],
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped' => ['delivered', 'returned'],
        'delivered' => ['completed', 'returned'],
        'completed' => [],
        'cancelled' => [],
        'returned' => ['refunded'],
        'refunded' => [],
    ];

    /**
     * Update order status
     *
     * @param SalesOrder $order
     * @param string $newStatus
     * @param string|null $notes
     * @param array $metadata
     * @return SalesOrder
     */
    public function updateStatus(
        SalesOrder $order,
        string $newStatus,
        ?string $notes = null,
        array $metadata = []
    ): SalesOrder {
        // Validate transition
        if (!$this->canTransitionTo($order, $newStatus)) {
            throw new \Exception(
                "Cannot transition from '{$order->status}' to '{$newStatus}'"
            );
        }

        return DB::transaction(function () use ($order, $newStatus, $notes, $metadata) {
            $oldStatus = $order->status;

            // Update order status
            $order->update([
                'status' => $newStatus,
                'metadata' => array_merge(
                    $order->metadata ?? [],
                    ['last_status_change' => now()->toIso8601String()]
                ),
            ]);

            // Add to status history
            $this->addStatusHistory($order, $oldStatus, $newStatus, $notes, $metadata);

            // Trigger status-specific actions
            $this->handleStatusChange($order, $newStatus);

            // Send status update notification (async)
            if ($order->order_source === 'ecommerce') {
                $this->notificationService->sendOrderStatusUpdate($order, $oldStatus, $newStatus, $notes);
            }

            // Send specific notifications for certain statuses
            if ($newStatus === 'shipped' && $order->order_source === 'ecommerce') {
                $this->notificationService->sendShippingNotification($order);
            }

            return $order->fresh();
        });
    }

    /**
     * Add entry to status history
     *
     * @param SalesOrder $order
     * @param string $oldStatus
     * @param string $newStatus
     * @param string|null $notes
     * @param array $metadata
     * @return void
     */
    public function addStatusHistory(
        SalesOrder $order,
        string $oldStatus,
        string $newStatus,
        ?string $notes = null,
        array $metadata = []
    ): void {
        $history = $order->metadata['status_history'] ?? [];

        $history[] = [
            'from' => $oldStatus,
            'to' => $newStatus,
            'changed_at' => now()->toIso8601String(),
            'changed_by' => auth()->id() ?? 'system',
            'notes' => $notes,
            'metadata' => $metadata,
        ];

        $order->update([
            'metadata' => array_merge(
                $order->metadata ?? [],
                ['status_history' => $history]
            ),
        ]);
    }

    /**
     * Get status history for order
     *
     * @param SalesOrder $order
     * @return array
     */
    public function getStatusHistory(SalesOrder $order): array
    {
        return $order->metadata['status_history'] ?? [];
    }

    /**
     * Check if order can transition to target status
     *
     * @param SalesOrder $order
     * @param string $targetStatus
     * @return bool
     */
    public function canTransitionTo(SalesOrder $order, string $targetStatus): bool
    {
        $currentStatus = $order->status;

        if (!isset($this->validTransitions[$currentStatus])) {
            return false;
        }

        return in_array($targetStatus, $this->validTransitions[$currentStatus]);
    }

    /**
     * Get available transitions for order
     *
     * @param SalesOrder $order
     * @return array
     */
    public function getAvailableTransitions(SalesOrder $order): array
    {
        $currentStatus = $order->status;

        return $this->validTransitions[$currentStatus] ?? [];
    }

    /**
     * Handle status-specific actions
     *
     * @param SalesOrder $order
     * @param string $newStatus
     * @return void
     */
    private function handleStatusChange(SalesOrder $order, string $newStatus): void
    {
        switch ($newStatus) {
            case 'shipped':
                // Generate tracking number if not exists
                if (!$order->tracking_number) {
                    $order->update([
                        'tracking_number' => $this->generateTrackingNumber(),
                    ]);
                }
                break;

            case 'delivered':
                // Mark as delivered
                $order->update([
                    'metadata' => array_merge(
                        $order->metadata ?? [],
                        ['delivered_at' => now()->toIso8601String()]
                    ),
                ]);
                break;

            case 'completed':
                // Mark as completed
                $order->update([
                    'metadata' => array_merge(
                        $order->metadata ?? [],
                        ['completed_at' => now()->toIso8601String()]
                    ),
                ]);
                break;

            case 'cancelled':
                // Mark as cancelled
                $order->update([
                    'metadata' => array_merge(
                        $order->metadata ?? [],
                        ['cancelled_at' => now()->toIso8601String()]
                    ),
                ]);
                break;
        }
    }

    /**
     * Mark order as shipped
     *
     * @param SalesOrder $order
     * @param string|null $trackingNumber
     * @param string|null $trackingUrl
     * @param string|null $carrier
     * @return SalesOrder
     */
    public function markAsShipped(
        SalesOrder $order,
        ?string $trackingNumber = null,
        ?string $trackingUrl = null,
        ?string $carrier = null
    ): SalesOrder {
        $metadata = [];

        if ($carrier) {
            $metadata['carrier'] = $carrier;
        }

        $order->update([
            'tracking_number' => $trackingNumber ?? $this->generateTrackingNumber(),
            'tracking_url' => $trackingUrl,
        ]);

        return $this->updateStatus($order, 'shipped', 'Order shipped', $metadata);
    }

    /**
     * Mark order as delivered
     *
     * @param SalesOrder $order
     * @param string|null $notes
     * @return SalesOrder
     */
    public function markAsDelivered(SalesOrder $order, ?string $notes = null): SalesOrder
    {
        return $this->updateStatus($order, 'delivered', $notes ?? 'Order delivered successfully');
    }

    /**
     * Cancel order
     *
     * @param SalesOrder $order
     * @param string|null $reason
     * @return SalesOrder
     */
    public function cancelOrder(SalesOrder $order, ?string $reason = null): SalesOrder
    {
        if (!$this->canTransitionTo($order, 'cancelled')) {
            throw new \Exception('Order cannot be cancelled in current status');
        }

        return $this->updateStatus($order, 'cancelled', $reason ?? 'Order cancelled by customer');
    }

    /**
     * Generate tracking number
     *
     * @return string
     */
    private function generateTrackingNumber(): string
    {
        $prefix = 'TRK';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(md5(uniqid()), 0, 8));

        return "{$prefix}-{$date}-{$random}";
    }
}
