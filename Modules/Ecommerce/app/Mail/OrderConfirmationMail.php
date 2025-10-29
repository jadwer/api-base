<?php

namespace Modules\Ecommerce\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Sales\Models\SalesOrder;

class OrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public SalesOrder $order;
    public array $orderSummary;
    public ?array $shippingInfo;
    public bool $isAdmin;

    /**
     * Create a new message instance.
     */
    public function __construct(SalesOrder $order, bool $isAdmin = false)
    {
        $this->order = $order->load(['items.product', 'customer', 'checkoutSession']);
        $this->isAdmin = $isAdmin;

        $notificationService = app(\Modules\Ecommerce\Services\Notifications\OrderNotificationService::class);
        $this->orderSummary = $notificationService->getOrderSummary($order);
        $this->shippingInfo = $notificationService->getShippingInfo($order);
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = $this->isAdmin
            ? 'New Order Received - ' . $this->order->order_number
            : 'Order Confirmation - ' . $this->order->order_number;

        return $this->subject($subject)
            ->view('ecommerce::emails.order-confirmation')
            ->with([
                'order' => $this->order,
                'orderSummary' => $this->orderSummary,
                'shippingInfo' => $this->shippingInfo,
                'isAdmin' => $this->isAdmin,
            ]);
    }
}
