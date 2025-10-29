<?php

namespace Modules\Ecommerce\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Sales\Models\SalesOrder;
use Modules\Ecommerce\Models\PaymentTransaction;
use Modules\Ecommerce\Services\Notifications\OrderNotificationService;
use Illuminate\Support\Facades\Log;

class SendOrderNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $notificationType;
    public int $entityId;
    public array $metadata;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     *
     * @param string $notificationType
     * @param int $entityId
     * @param array $metadata
     */
    public function __construct(string $notificationType, int $entityId, array $metadata = [])
    {
        $this->notificationType = $notificationType;
        $this->entityId = $entityId;
        $this->metadata = $metadata;
    }

    /**
     * Execute the job.
     */
    public function handle(OrderNotificationService $notificationService): void
    {
        try {
            switch ($this->notificationType) {
                case 'order_confirmation':
                    $this->handleOrderConfirmation($notificationService);
                    break;

                case 'payment_confirmation':
                    $this->handlePaymentConfirmation($notificationService);
                    break;

                case 'shipping_notification':
                    $this->handleShippingNotification($notificationService);
                    break;

                case 'status_update':
                    $this->handleStatusUpdate($notificationService);
                    break;

                case 'order_cancellation':
                    $this->handleOrderCancellation($notificationService);
                    break;

                case 'return_request':
                    $this->handleReturnRequest($notificationService);
                    break;

                default:
                    Log::warning('Unknown notification type: ' . $this->notificationType);
            }

            Log::info('Order notification sent successfully', [
                'type' => $this->notificationType,
                'entity_id' => $this->entityId,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send order notification', [
                'type' => $this->notificationType,
                'entity_id' => $this->entityId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle order confirmation notification
     */
    protected function handleOrderConfirmation(OrderNotificationService $notificationService): void
    {
        $order = SalesOrder::findOrFail($this->entityId);
        $notificationService->sendOrderConfirmationNow($order);
    }

    /**
     * Handle payment confirmation notification
     */
    protected function handlePaymentConfirmation(OrderNotificationService $notificationService): void
    {
        $transaction = PaymentTransaction::findOrFail($this->entityId);
        $notificationService->sendPaymentConfirmationNow($transaction);
    }

    /**
     * Handle shipping notification
     */
    protected function handleShippingNotification(OrderNotificationService $notificationService): void
    {
        $order = SalesOrder::findOrFail($this->entityId);
        $notificationService->sendShippingNotificationNow($order);
    }

    /**
     * Handle status update notification
     */
    protected function handleStatusUpdate(OrderNotificationService $notificationService): void
    {
        $order = SalesOrder::findOrFail($this->entityId);

        $notificationService->sendOrderStatusUpdateNow(
            $order,
            $this->metadata['previous_status'],
            $this->metadata['new_status'],
            $this->metadata['notes'] ?? null
        );
    }

    /**
     * Handle order cancellation notification
     */
    protected function handleOrderCancellation(OrderNotificationService $notificationService): void
    {
        $order = SalesOrder::findOrFail($this->entityId);

        $notificationService->sendOrderCancellationNow(
            $order,
            $this->metadata['reason'] ?? null
        );
    }

    /**
     * Handle return request notification
     */
    protected function handleReturnRequest(OrderNotificationService $notificationService): void
    {
        $order = SalesOrder::findOrFail($this->entityId);

        $notificationService->sendReturnRequestConfirmationNow(
            $order,
            $this->metadata['return_data']
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Order notification job failed permanently', [
            'type' => $this->notificationType,
            'entity_id' => $this->entityId,
            'error' => $exception->getMessage(),
        ]);

        // Optionally notify admin about the failure
        // You could send an internal notification or create a failed notification log
    }
}
