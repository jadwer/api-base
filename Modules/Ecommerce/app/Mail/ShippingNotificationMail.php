<?php

namespace Modules\Ecommerce\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Sales\Models\SalesOrder;

class ShippingNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public SalesOrder $order;
    public array $orderSummary;
    public ?array $shippingInfo;
    public ?array $trackingInfo;

    /**
     * Create a new message instance.
     */
    public function __construct(SalesOrder $order)
    {
        $this->order = $order->load(['items.product', 'customer']);

        $notificationService = app(\Modules\Ecommerce\Services\Notifications\OrderNotificationService::class);
        $this->orderSummary = $notificationService->getOrderSummary($order);
        $this->shippingInfo = $notificationService->getShippingInfo($order);
        $this->trackingInfo = $notificationService->getTrackingInfo($order);
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Your Order Has Been Shipped - ' . $this->order->order_number)
            ->view('ecommerce::emails.shipping-notification')
            ->with([
                'order' => $this->order,
                'orderSummary' => $this->orderSummary,
                'shippingInfo' => $this->shippingInfo,
                'trackingInfo' => $this->trackingInfo,
            ]);
    }
}
