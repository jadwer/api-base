<?php

namespace Modules\Ecommerce\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\MailerManager\Traits\UsesEmailTemplate;
use Modules\Sales\Models\SalesOrder;

class OrderStatusUpdateMail extends Mailable
{
    use Queueable, SerializesModels, UsesEmailTemplate;

    public SalesOrder $order;
    public string $previousStatus;
    public string $newStatus;
    public ?string $notes;
    public array $orderSummary;

    protected function getRegistryKey(): string
    {
        return 'ecommerce.order_status_update';
    }

    /**
     * Create a new message instance.
     */
    public function __construct(SalesOrder $order, string $previousStatus, string $newStatus, ?string $notes = null)
    {
        $this->order = $order->load(['items.product', 'customer']);
        $this->previousStatus = $previousStatus;
        $this->newStatus = $newStatus;
        $this->notes = $notes;

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

        $statusLabel = $this->getStatusLabel($this->newStatus);

        return $this->subject('Order Status Update: ' . $statusLabel . ' - ' . $this->order->order_number)
            ->view('ecommerce::emails.order-status-update')
            ->with([
                'order' => $this->order,
                'previousStatus' => $this->previousStatus,
                'newStatus' => $this->newStatus,
                'newStatusLabel' => $statusLabel,
                'notes' => $this->notes,
                'orderSummary' => $this->orderSummary,
            ]);
    }

    protected function getTemplateVariables(): array
    {
        return [
            'order_number' => $this->orderSummary['order_number'],
            'customer_name' => $this->order->customer?->name ?? 'Cliente',
            'previous_status' => $this->getStatusLabel($this->previousStatus),
            'new_status' => $this->getStatusLabel($this->newStatus),
            'notes' => $this->notes ?? '',
            'company_name' => app(\Modules\AppConfig\Services\AppSettingResolver::class)->get('company.name', config('app.name', 'Demo Company')),
        ];
    }

    /**
     * Get human-readable status label
     */
    protected function getStatusLabel(string $status): string
    {
        $labels = [
            'draft' => 'Draft',
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'processing' => 'Processing',
            'shipped' => 'Shipped',
            'delivered' => 'Delivered',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded',
            'returned' => 'Returned',
        ];

        return $labels[$status] ?? ucfirst($status);
    }
}
