<?php

namespace Modules\Ecommerce\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\MailerManager\Traits\UsesEmailTemplate;
use Modules\Sales\Models\SalesOrder;

class OrderReturnRequestMail extends Mailable
{
    use Queueable, SerializesModels, UsesEmailTemplate;

    public SalesOrder $order;
    public array $returnData;
    public array $orderSummary;
    public bool $isAdmin;

    protected function getRegistryKey(): string
    {
        return $this->isAdmin
            ? 'ecommerce.order_return.admin'
            : 'ecommerce.order_return.customer';
    }

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
        $templateMail = $this->buildWithTemplate();
        if ($templateMail) {
            return $templateMail;
        }

        $subject = $this->isAdmin
            ? 'Nueva solicitud de devolución - ' . $this->order->order_number
            : 'Recibimos tu solicitud de devolución - ' . $this->order->order_number;

        return $this->subject($subject)
            ->view('ecommerce::emails.order-return-request')
            ->with([
                'order' => $this->order,
                'returnData' => $this->returnData,
                'orderSummary' => $this->orderSummary,
                'isAdmin' => $this->isAdmin,
            ]);
    }

    protected function getTemplateVariables(): array
    {
        return [
            'order_number' => $this->orderSummary['order_number'],
            'customer_name' => $this->order->customer?->name ?? 'Cliente',
            'customer_email' => $this->order->customer?->email ?? '',
            'return_reason' => $this->returnData['reason'] ?? '',
            'company_name' => app(\Modules\AppConfig\Services\AppSettingResolver::class)->get('company.name', config('app.name', 'Tu Empresa')),
        ];
    }
}
