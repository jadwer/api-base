<?php

namespace Modules\Ecommerce\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Ecommerce\Models\PaymentTransaction;
use Modules\Sales\Models\SalesOrder;

class PaymentConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public PaymentTransaction $transaction;
    public SalesOrder $order;
    public array $orderSummary;

    /**
     * Create a new message instance.
     */
    public function __construct(PaymentTransaction $transaction, SalesOrder $order)
    {
        $this->transaction = $transaction;
        $this->order = $order->load(['items.product', 'customer']);

        $notificationService = app(\Modules\Ecommerce\Services\Notifications\OrderNotificationService::class);
        $this->orderSummary = $notificationService->getOrderSummary($order);
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Payment Confirmation - ' . $this->order->order_number)
            ->view('ecommerce::emails.payment-confirmation')
            ->with([
                'transaction' => $this->transaction,
                'order' => $this->order,
                'orderSummary' => $this->orderSummary,
            ]);
    }
}
