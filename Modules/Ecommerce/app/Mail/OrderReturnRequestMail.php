<?php

namespace Modules\Ecommerce\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Sales\Models\SalesOrder;

class OrderReturnRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public SalesOrder $order;
    public array $returnData;
    public array $orderSummary;
    public bool $isAdmin;

    /**
     * Create a new message instance.
     */
    public function __construct(SalesOrder $order, array $returnData, bool $isAdmin = false)
    {
        $this->order = $order->load(['items.product', 'customer']);
        $this->returnData = $returnData;
        $this->isAdmin = $isAdmin;

        $notificationService = app(\Modules\Ecommerce\Services\Notifications\OrderNotificationService::class);
        $this->orderSummary = $notificationService->getOrderSummary($order);
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = $this->isAdmin
            ? 'Return Request Received - ' . $this->order->order_number
            : 'Return Request Confirmation - ' . $this->order->order_number;

        return $this->subject($subject)
            ->view('ecommerce::emails.order-return-request')
            ->with([
                'order' => $this->order,
                'returnData' => $this->returnData,
                'orderSummary' => $this->orderSummary,
                'isAdmin' => $this->isAdmin,
            ]);
    }
}
