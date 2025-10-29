<?php

namespace Modules\Ecommerce\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Sales\Models\SalesOrder;

class OrderCancellationMail extends Mailable
{
    use Queueable, SerializesModels;

    public SalesOrder $order;
    public ?string $reason;
    public array $orderSummary;

    /**
     * Create a new message instance.
     */
    public function __construct(SalesOrder $order, ?string $reason = null)
    {
        $this->order = $order->load(['items.product', 'customer']);
        $this->reason = $reason;

        $notificationService = app(\Modules\Ecommerce\Services\Notifications\OrderNotificationService::class);
        $this->orderSummary = $notificationService->getOrderSummary($order);
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Order Cancelled - ' . $this->order->order_number)
            ->view('ecommerce::emails.order-cancellation')
            ->with([
                'order' => $this->order,
                'reason' => $this->reason,
                'orderSummary' => $this->orderSummary,
            ]);
    }
}
