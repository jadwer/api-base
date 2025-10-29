<?php

namespace Modules\Ecommerce\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Sales\Models\SalesOrder;

class OrderStatusUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    public SalesOrder $order;
    public string $previousStatus;
    public string $newStatus;
    public ?string $notes;
    public array $orderSummary;

    /**
     * Create a new message instance.
     */
    public function __construct(SalesOrder $order, string $previousStatus, string $newStatus, ?string $notes = null)
    {
        $this->order = $order->load(['items.product', 'customer']);
        $this->previousStatus = $previousStatus;
        $this->newStatus = $newStatus;
        $this->notes = $notes;

        $notificationService = app(\Modules\Ecommerce\Services\Notifications\OrderNotificationService::class);
        $this->orderSummary = $notificationService->getOrderSummary($order);
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $statusLabel = $this->getStatusLabel($this->newStatus);

        return $this->subject('Order Status Update: ' . $statusLabel . ' - ' . $this->order->order_number)
            ->view('ecommerce::emails.order-status-update')
            ->with([
                'order' => $this->order,
                'previousStatus' => $this->previousStatus,
                'newStatus' => $this->newStatus,
                'newStatusLabel' => $statusLabel,
                'notes' => $this->notes,
                'orderSummary' => $this->orderSummary,
            ]);
    }

    /**
     * Get human-readable status label
     */
    protected function getStatusLabel(string $status): string
    {
        $labels = [
            'draft' => 'Draft',
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'processing' => 'Processing',
            'shipped' => 'Shipped',
            'delivered' => 'Delivered',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded',
            'returned' => 'Returned',
        ];

        return $labels[$status] ?? ucfirst($status);
    }
}
