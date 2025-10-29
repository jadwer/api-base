<?php

namespace Modules\Ecommerce\Services\Payment;

use Modules\Ecommerce\Models\CheckoutSession;
use Modules\Ecommerce\Models\PaymentTransaction;
use Modules\Ecommerce\Services\CheckoutService;
use Modules\Ecommerce\Services\Notifications\OrderNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    private CheckoutService $checkoutService;
    private OrderNotificationService $notificationService;
    private array $gateways = [];

    public function __construct(CheckoutService $checkoutService, OrderNotificationService $notificationService)
    {
        $this->checkoutService = $checkoutService;
        $this->notificationService = $notificationService;
        $this->registerGateways();
    }

    /**
     * Register available payment gateways
     *
     * @return void
     */
    private function registerGateways(): void
    {
        // Register mock gateway (for testing)
        $this->gateways['mock'] = app(MockPaymentGateway::class);

        // TODO: Register Stripe gateway when implemented
        // $this->gateways['stripe'] = app(StripePaymentGateway::class);
    }

    /**
     * Get payment gateway by name
     *
     * @param string $gatewayName
     * @return PaymentGatewayInterface
     */
    private function getGateway(string $gatewayName): PaymentGatewayInterface
    {
        if (!isset($this->gateways[$gatewayName])) {
            throw new \Exception("Payment gateway '{$gatewayName}' not found");
        }

        return $this->gateways[$gatewayName];
    }

    /**
     * Process payment for checkout session
     *
     * @param CheckoutSession $session
     * @param string $gateway
     * @param array $paymentData
     * @return PaymentTransaction
     */
    public function processPayment(CheckoutSession $session, string $gateway, array $paymentData): PaymentTransaction
    {
        // Validate session can proceed to payment
        if (!$session->can_proceed_to_payment) {
            throw new \Exception('Checkout session is not ready for payment');
        }

        if ($session->getIsExpiredAttribute()) {
            throw new \Exception('Checkout session has expired');
        }

        return DB::transaction(function () use ($session, $gateway, $paymentData) {
            $paymentGateway = $this->getGateway($gateway);

            // Create payment intent
            $paymentIntent = $paymentGateway->createPaymentIntent($session, $paymentData);

            if (isset($paymentIntent['error']) || $paymentIntent['status'] === 'failed') {
                throw new \Exception($paymentIntent['error'] ?? 'Payment failed');
            }

            // Create payment transaction record
            $transaction = PaymentTransaction::create([
                'checkout_session_id' => $session->id,
                'transaction_id' => $paymentIntent['payment_intent_id'],
                'payment_gateway' => $gateway,
                'payment_method' => $paymentData['payment_method'] ?? 'card',
                'status' => 'pending',
                'amount' => $session->total_amount,
                'currency' => $session->currency,
                'gateway_response' => $paymentIntent,
                'metadata' => [
                    'payment_data' => $paymentData,
                    'created_at' => now()->toIso8601String(),
                ],
            ]);

            // Update checkout session
            $session->update([
                'status' => 'payment_pending',
                'payment_method' => $gateway,
                'payment_intent_id' => $paymentIntent['payment_intent_id'],
            ]);

            return $transaction;
        });
    }

    /**
     * Confirm/capture payment
     *
     * @param PaymentTransaction $transaction
     * @return PaymentTransaction
     */
    public function confirmPayment(PaymentTransaction $transaction): PaymentTransaction
    {
        if ($transaction->status !== 'pending') {
            throw new \Exception('Payment transaction is not in pending status');
        }

        return DB::transaction(function () use ($transaction) {
            $gateway = $this->getGateway($transaction->payment_gateway);

            // Capture payment from gateway
            $result = $gateway->capturePayment($transaction->transaction_id);

            if ($result['status'] === 'failed') {
                $transaction->markAsFailed($result['error'] ?? 'Payment capture failed');
                throw new \Exception($result['error'] ?? 'Payment capture failed');
            }

            // Update transaction
            $transaction->markAsCaptured();
            $transaction->update([
                'gateway_response' => array_merge(
                    $transaction->gateway_response ?? [],
                    ['capture_response' => $result]
                ),
            ]);

            // Update checkout session
            $session = $transaction->checkoutSession;
            $session->update(['status' => 'payment_confirmed']);

            // Complete checkout and create order
            $order = $this->checkoutService->completeCheckout($session);

            // Link transaction to order
            $transaction->update(['sales_order_id' => $order->id]);

            // Send payment confirmation email
            $this->notificationService->sendPaymentConfirmation($transaction->fresh());

            // Send order confirmation email
            $this->notificationService->sendOrderConfirmation($order);

            return $transaction->fresh();
        });
    }

    /**
     * Verify payment status
     *
     * @param PaymentTransaction $transaction
     * @return bool
     */
    public function verifyPayment(PaymentTransaction $transaction): bool
    {
        $gateway = $this->getGateway($transaction->payment_gateway);
        $status = $gateway->getPaymentStatus($transaction->transaction_id);

        // Update transaction status if different
        if ($status !== $transaction->status) {
            $transaction->update(['status' => $status]);
        }

        return in_array($status, ['authorized', 'captured']);
    }

    /**
     * Refund payment
     *
     * @param PaymentTransaction $transaction
     * @param float $amount
     * @param string|null $reason
     * @return PaymentTransaction
     */
    public function refundPayment(PaymentTransaction $transaction, float $amount, ?string $reason = null): PaymentTransaction
    {
        if (!$transaction->canBeRefunded()) {
            throw new \Exception('Payment cannot be refunded');
        }

        if ($amount > $transaction->amount) {
            throw new \Exception('Refund amount cannot exceed payment amount');
        }

        return DB::transaction(function () use ($transaction, $amount, $reason) {
            $gateway = $this->getGateway($transaction->payment_gateway);

            // Process refund
            $result = $gateway->refundPayment($transaction->transaction_id, $amount, $reason);

            if ($result['status'] === 'failed') {
                throw new \Exception($result['error'] ?? 'Refund failed');
            }

            // Update transaction
            $transaction->markAsRefunded();
            $transaction->update([
                'gateway_response' => array_merge(
                    $transaction->gateway_response ?? [],
                    ['refund_response' => $result]
                ),
                'metadata' => array_merge(
                    $transaction->metadata ?? [],
                    [
                        'refund_amount' => $amount,
                        'refund_reason' => $reason,
                        'refunded_at' => now()->toIso8601String(),
                    ]
                ),
            ]);

            return $transaction->fresh();
        });
    }

    /**
     * Cancel pending payment
     *
     * @param PaymentTransaction $transaction
     * @return void
     */
    public function cancelPayment(PaymentTransaction $transaction): void
    {
        if ($transaction->status !== 'pending') {
            throw new \Exception('Can only cancel pending payments');
        }

        $gateway = $this->getGateway($transaction->payment_gateway);
        $result = $gateway->cancelPayment($transaction->transaction_id);

        $transaction->update([
            'status' => 'cancelled',
            'gateway_response' => array_merge(
                $transaction->gateway_response ?? [],
                ['cancel_response' => $result]
            ),
        ]);
    }

    /**
     * Handle webhook from payment gateway
     *
     * @param string $gatewayName
     * @param array $payload
     * @param string|null $signature
     * @return void
     */
    public function handleWebhook(string $gatewayName, array $payload, ?string $signature = null): void
    {
        $gateway = $this->getGateway($gatewayName);

        // Verify signature if provided
        if ($signature && !$gateway->verifyWebhookSignature($payload, $signature)) {
            throw new \Exception('Invalid webhook signature');
        }

        // Process webhook
        $gateway->handleWebhook($payload, $signature);

        // TODO: Implement webhook event processing
        // - Update transaction status
        // - Send notifications
        // - Log events
    }

    /**
     * Get available payment gateways
     *
     * @return array
     */
    public function getAvailableGateways(): array
    {
        return array_keys($this->gateways);
    }
}
