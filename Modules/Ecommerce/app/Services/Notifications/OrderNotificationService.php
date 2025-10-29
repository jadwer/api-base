<?php

namespace Modules\Ecommerce\Services\Notifications;

use Illuminate\Support\Facades\Mail;
use Modules\Sales\Models\SalesOrder;
use Modules\Ecommerce\Models\CheckoutSession;
use Modules\Ecommerce\Models\PaymentTransaction;
use Modules\Ecommerce\Mail\OrderConfirmationMail;
use Modules\Ecommerce\Mail\PaymentConfirmationMail;
use Modules\Ecommerce\Mail\ShippingNotificationMail;
use Modules\Ecommerce\Mail\OrderStatusUpdateMail;
use Modules\Ecommerce\Mail\OrderCancellationMail;
use Modules\Ecommerce\Mail\OrderReturnRequestMail;
use Modules\Ecommerce\Jobs\SendOrderNotificationJob;

class OrderNotificationService
{
    /**
     * Send order confirmation email after order is created
     */
    public function sendOrderConfirmation(SalesOrder $order, bool $async = true): void
    {
        if ($async) {
            SendOrderNotificationJob::dispatch(
                'order_confirmation',
                $order->id
            )->onQueue('emails');
        } else {
            $this->sendOrderConfirmationNow($order);
        }
    }

    /**
     * Send order confirmation email immediately
     */
    public function sendOrderConfirmationNow(SalesOrder $order): void
    {
        $recipientEmail = $this->getOrderEmail($order);

        if (!$recipientEmail) {
            return;
        }

        Mail::to($recipientEmail)->send(new OrderConfirmationMail($order));
    }

    /**
     * Send payment confirmation email after payment is captured
     */
    public function sendPaymentConfirmation(PaymentTransaction $transaction, bool $async = true): void
    {
        if ($async) {
            SendOrderNotificationJob::dispatch(
                'payment_confirmation',
                $transaction->id
            )->onQueue('emails');
        } else {
            $this->sendPaymentConfirmationNow($transaction);
        }
    }

    /**
     * Send payment confirmation email immediately
     */
    public function sendPaymentConfirmationNow(PaymentTransaction $transaction): void
    {
        $order = $transaction->salesOrder;

        if (!$order) {
            return;
        }

        $recipientEmail = $this->getOrderEmail($order);

        if (!$recipientEmail) {
            return;
        }

        Mail::to($recipientEmail)->send(new PaymentConfirmationMail($transaction, $order));
    }

    /**
     * Send shipping notification when order is shipped
     */
    public function sendShippingNotification(SalesOrder $order, bool $async = true): void
    {
        if ($async) {
            SendOrderNotificationJob::dispatch(
                'shipping_notification',
                $order->id
            )->onQueue('emails');
        } else {
            $this->sendShippingNotificationNow($order);
        }
    }

    /**
     * Send shipping notification immediately
     */
    public function sendShippingNotificationNow(SalesOrder $order): void
    {
        $recipientEmail = $this->getOrderEmail($order);

        if (!$recipientEmail) {
            return;
        }

        Mail::to($recipientEmail)->send(new ShippingNotificationMail($order));
    }

    /**
     * Send order status update notification
     */
    public function sendOrderStatusUpdate(SalesOrder $order, string $previousStatus, string $newStatus, ?string $notes = null, bool $async = true): void
    {
        if ($async) {
            SendOrderNotificationJob::dispatch(
                'status_update',
                $order->id,
                ['previous_status' => $previousStatus, 'new_status' => $newStatus, 'notes' => $notes]
            )->onQueue('emails');
        } else {
            $this->sendOrderStatusUpdateNow($order, $previousStatus, $newStatus, $notes);
        }
    }

    /**
     * Send order status update immediately
     */
    public function sendOrderStatusUpdateNow(SalesOrder $order, string $previousStatus, string $newStatus, ?string $notes = null): void
    {
        $recipientEmail = $this->getOrderEmail($order);

        if (!$recipientEmail) {
            return;
        }

        Mail::to($recipientEmail)->send(new OrderStatusUpdateMail($order, $previousStatus, $newStatus, $notes));
    }

    /**
     * Send order cancellation notification
     */
    public function sendOrderCancellation(SalesOrder $order, ?string $reason = null, bool $async = true): void
    {
        if ($async) {
            SendOrderNotificationJob::dispatch(
                'order_cancellation',
                $order->id,
                ['reason' => $reason]
            )->onQueue('emails');
        } else {
            $this->sendOrderCancellationNow($order, $reason);
        }
    }

    /**
     * Send order cancellation immediately
     */
    public function sendOrderCancellationNow(SalesOrder $order, ?string $reason = null): void
    {
        $recipientEmail = $this->getOrderEmail($order);

        if (!$recipientEmail) {
            return;
        }

        Mail::to($recipientEmail)->send(new OrderCancellationMail($order, $reason));
    }

    /**
     * Send return request confirmation
     */
    public function sendReturnRequestConfirmation(SalesOrder $order, array $returnData, bool $async = true): void
    {
        if ($async) {
            SendOrderNotificationJob::dispatch(
                'return_request',
                $order->id,
                ['return_data' => $returnData]
            )->onQueue('emails');
        } else {
            $this->sendReturnRequestConfirmationNow($order, $returnData);
        }
    }

    /**
     * Send return request confirmation immediately
     */
    public function sendReturnRequestConfirmationNow(SalesOrder $order, array $returnData): void
    {
        $recipientEmail = $this->getOrderEmail($order);

        if (!$recipientEmail) {
            return;
        }

        Mail::to($recipientEmail)->send(new OrderReturnRequestMail($order, $returnData));
    }

    /**
     * Get the email address to send notifications to
     */
    protected function getOrderEmail(SalesOrder $order): ?string
    {
        // Try checkout session email first
        if ($order->checkoutSession && $order->checkoutSession->contact_email) {
            return $order->checkoutSession->contact_email;
        }

        // Try customer contact email
        if ($order->customer && $order->customer->email) {
            return $order->customer->email;
        }

        // Try user email
        if ($order->user && $order->user->email) {
            return $order->user->email;
        }

        return null;
    }

    /**
     * Get formatted order summary for emails
     */
    public function getOrderSummary(SalesOrder $order): array
    {
        $items = $order->items->map(function ($item) {
            return [
                'name' => $item->product ? $item->product->name : 'Unknown Product',
                'sku' => $item->product ? $item->product->sku : 'N/A',
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'subtotal' => $item->quantity * $item->unit_price,
            ];
        });

        return [
            'order_number' => $order->order_number,
            'order_date' => $order->order_date->format('Y-m-d'),
            'status' => $order->status,
            'items' => $items->toArray(),
            'subtotal' => $order->subtotal_amount ?? $order->total_amount,
            'tax' => $order->tax_amount ?? 0,
            'shipping' => $order->shipping_amount ?? 0,
            'discount' => $order->discount_amount ?? 0,
            'total' => $order->total_amount,
            'currency' => $order->currency ?? 'MXN',
        ];
    }

    /**
     * Get shipping information for emails
     */
    public function getShippingInfo(SalesOrder $order): ?array
    {
        $shippingAddress = $order->shipping_address;

        if (!$shippingAddress || !is_array($shippingAddress)) {
            return null;
        }

        return [
            'recipient' => $shippingAddress['name'] ?? ($order->customer ? $order->customer->name : 'N/A'),
            'address_line1' => $shippingAddress['address_line1'] ?? '',
            'address_line2' => $shippingAddress['address_line2'] ?? '',
            'city' => $shippingAddress['city'] ?? '',
            'state' => $shippingAddress['state'] ?? '',
            'country' => $shippingAddress['country'] ?? '',
            'postal_code' => $shippingAddress['postal_code'] ?? '',
            'phone' => $shippingAddress['phone'] ?? '',
        ];
    }

    /**
     * Get tracking information for emails
     */
    public function getTrackingInfo(SalesOrder $order): ?array
    {
        if (!$order->tracking_number) {
            return null;
        }

        return [
            'tracking_number' => $order->tracking_number,
            'tracking_url' => $order->tracking_url ?? config('app.url') . '/orders/' . $order->id . '/tracking',
            'carrier' => $order->metadata['carrier'] ?? 'N/A',
            'estimated_delivery' => $order->estimated_delivery_date ?? null,
        ];
    }

    /**
     * Send notification to admin about new order
     */
    public function notifyAdminNewOrder(SalesOrder $order): void
    {
        $adminEmail = config('mail.admin_notification_email');

        if (!$adminEmail) {
            return;
        }

        // This could be a different Mailable class for admin notifications
        Mail::to($adminEmail)->send(new OrderConfirmationMail($order, true));
    }

    /**
     * Send notification to admin about return request
     */
    public function notifyAdminReturnRequest(SalesOrder $order, array $returnData): void
    {
        $adminEmail = config('mail.admin_notification_email');

        if (!$adminEmail) {
            return;
        }

        Mail::to($adminEmail)->send(new OrderReturnRequestMail($order, $returnData, true));
    }
}
