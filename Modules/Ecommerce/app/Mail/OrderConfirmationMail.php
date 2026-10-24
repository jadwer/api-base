<?php

namespace Modules\Ecommerce\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\MailerManager\Traits\UsesEmailTemplate;
use Modules\Sales\Models\SalesOrder;

class OrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels, UsesEmailTemplate;

    public SalesOrder $order;
    public array $orderSummary;
    public ?array $shippingInfo;
    public bool $isAdmin;

    protected function getRegistryKey(): string
    {
        return $this->isAdmin
            ? 'ecommerce.order_confirmation.admin'
            : 'ecommerce.order_confirmation.customer';
    }

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
        $templateMail = $this->buildWithTemplate();
        if ($templateMail) {
            return $templateMail;
        }

        $subject = $this->isAdmin
            ? 'Nuevo pedido recibido - ' . $this->order->order_number
            : 'Confirmación de pedido - ' . $this->order->order_number;

        return $this->subject($subject)
            ->view('ecommerce::emails.order-confirmation')
            ->with([
                'order' => $this->order,
                'orderSummary' => $this->orderSummary,
                'shippingInfo' => $this->shippingInfo,
                'isAdmin' => $this->isAdmin,
                'statusLabel' => OrderStatusUpdateMail::statusLabel((string) ($this->orderSummary['status'] ?? '')),
            ]);
    }

    protected function getTemplateVariables(): array
    {
        // La plantilla recorre {{#each items}} con {name, sku, quantity,
        // price/unit_price, subtotal}; antes recibia un texto plano y la tabla
        // del correo salia vacia.
        $items = array_map(fn (array $item) => [
            'name' => $item['name'],
            'sku' => $item['sku'],
            'quantity' => $item['quantity'],
            'price' => (float) $item['unit_price'],
            'unit_price' => (float) $item['unit_price'],
            'subtotal' => (float) $item['subtotal'],
        ], $this->orderSummary['items']);

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
            'customer_email' => $this->order->customer?->email ?? '',
            'order_date' => $this->orderSummary['order_date'],
            'status' => OrderStatusUpdateMail::statusLabel((string) ($this->orderSummary['status'] ?? '')),
            'items' => $items,
            'subtotal' => number_format($this->orderSummary['subtotal'] ?? 0, 2),
            'tax' => number_format($this->orderSummary['tax'] ?? 0, 2),
            'total' => number_format($this->orderSummary['total'] ?? 0, 2),
            'currency' => $this->orderSummary['currency'] ?? 'MXN',
            'shipping_address' => $shippingAddress,
            'company_name' => app(\Modules\AppConfig\Services\AppSettingResolver::class)->get('company.name', config('app.name', 'Tu Empresa')),
        ];
    }
}
