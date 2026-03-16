<?php

namespace Modules\Ecommerce\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Ecommerce\Models\PaymentTransaction;
use Modules\MailerManager\Traits\UsesEmailTemplate;
use Modules\Sales\Models\SalesOrder;

class PaymentConfirmationMail extends Mailable
{
    use Queueable, SerializesModels, UsesEmailTemplate;

    public PaymentTransaction $transaction;
    public SalesOrder $order;
    public array $orderSummary;

    protected function getRegistryKey(): string
    {
        return 'ecommerce.payment_confirmation';
    }

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
        $templateMail = $this->buildWithTemplate();
        if ($templateMail) {
            return $templateMail;
        }

        return $this->subject('Payment Confirmation - ' . $this->order->order_number)
            ->view('ecommerce::emails.payment-confirmation')
            ->with([
                'transaction' => $this->transaction,
                'order' => $this->order,
                'orderSummary' => $this->orderSummary,
            ]);
    }

    protected function getTemplateVariables(): array
    {
        return [
            'order_number' => $this->orderSummary['order_number'],
            'customer_name' => $this->order->customer?->name ?? 'Cliente',
            'payment_amount' => number_format($this->transaction->amount ?? 0, 2),
            'payment_method' => $this->transaction->payment_method ?? '',
            'payment_date' => $this->transaction->created_at?->format('Y-m-d H:i') ?? '',
            'transaction_id' => $this->transaction->transaction_id ?? $this->transaction->id ?? '',
            'currency' => $this->orderSummary['currency'] ?? 'MXN',
            'company_name' => config('app.name', 'Labor Wasser de Mexico'),
        ];
    }
}
