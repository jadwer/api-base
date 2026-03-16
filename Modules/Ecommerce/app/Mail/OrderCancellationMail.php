<?php

namespace Modules\Ecommerce\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\MailerManager\Traits\UsesEmailTemplate;
use Modules\Sales\Models\SalesOrder;

class OrderCancellationMail extends Mailable
{
    use Queueable, SerializesModels, UsesEmailTemplate;

    public SalesOrder $order;
    public ?string $reason;
    public array $orderSummary;

    protected function getRegistryKey(): string
    {
        return 'ecommerce.order_cancellation';
    }

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
        $templateMail = $this->buildWithTemplate();
        if ($templateMail) {
            return $templateMail;
        }

        return $this->subject('Order Cancelled - ' . $this->order->order_number)
            ->view('ecommerce::emails.order-cancellation')
            ->with([
                'order' => $this->order,
                'reason' => $this->reason,
                'orderSummary' => $this->orderSummary,
            ]);
    }

    protected function getTemplateVariables(): array
    {
        return [
            'order_number' => $this->orderSummary['order_number'],
            'customer_name' => $this->order->customer?->name ?? 'Cliente',
            'cancellation_reason' => $this->reason ?? '',
            'total' => number_format($this->orderSummary['total'] ?? 0, 2),
            'currency' => $this->orderSummary['currency'] ?? 'MXN',
            'company_name' => config('app.name', 'Labor Wasser de Mexico'),
        ];
    }
}
