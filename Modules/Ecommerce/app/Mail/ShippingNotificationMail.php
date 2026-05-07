<?php

namespace Modules\Ecommerce\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\MailerManager\Traits\UsesEmailTemplate;
use Modules\Sales\Models\SalesOrder;

class ShippingNotificationMail extends Mailable
{
    use Queueable, SerializesModels, UsesEmailTemplate;

    public SalesOrder $order;
    public array $orderSummary;
    public ?array $shippingInfo;
    public ?array $trackingInfo;

    protected function getRegistryKey(): string
    {
        return 'ecommerce.shipping_notification';
    }

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
        $templateMail = $this->buildWithTemplate();
        if ($templateMail) {
            return $templateMail;
        }

        return $this->subject('Your Order Has Been Shipped - ' . $this->order->order_number)
            ->view('ecommerce::emails.shipping-notification')
            ->with([
                'order' => $this->order,
                'orderSummary' => $this->orderSummary,
                'shippingInfo' => $this->shippingInfo,
                'trackingInfo' => $this->trackingInfo,
            ]);
    }

    protected function getTemplateVariables(): array
    {
        $shippingAddress = '';
        if ($this->shippingInfo) {
            $parts = array_filter([
                $this->shippingInfo['address_line1'] ?? '',
                $this->shippingInfo['address_line2'] ?? '',
                $this->shippingInfo['city'] ?? '',
                $this->shippingInfo['state'] ?? '',
                $this->shippingInfo['postal_code'] ?? '',
                $this->shippingInfo['country'] ?? '',
            ]);
            $shippingAddress = implode(', ', $parts);
        }

        return [
            'order_number' => $this->orderSummary['order_number'],
            'customer_name' => $this->order->customer?->name ?? 'Cliente',
            'tracking_number' => $this->trackingInfo['tracking_number'] ?? '',
            'carrier' => $this->trackingInfo['carrier'] ?? '',
            'estimated_delivery' => $this->trackingInfo['estimated_delivery'] ?? '',
            'shipping_address' => $shippingAddress,
            'company_name' => app(\Modules\AppConfig\Services\AppSettingResolver::class)->get('company.name', config('app.name', 'Demo Company')),
        ];
    }
}
