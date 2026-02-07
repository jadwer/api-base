<?php

namespace Modules\Ecommerce\Services\Payment;

use Modules\Ecommerce\Models\CheckoutSession;
use Modules\Ecommerce\Models\PaymentTransaction;
use Modules\Ecommerce\Services\CheckoutService;
use Modules\Ecommerce\Services\Notifications\OrderNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        // Register Stripe gateway if configured
        if (config('services.stripe.secret')) {
            $this->gateways['stripe'] = app(StripePaymentGateway::class);
        }
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
                'gateway' => $gateway,
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
            $gateway = $this->getGateway($transaction->gateway);

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
        $gateway = $this->getGateway($transaction->gateway);
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
            $gateway = $this->getGateway($transaction->gateway);

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

        $gateway = $this->getGateway($transaction->gateway);
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

        // Verify webhook signature - always required
        if (empty($signature)) {
            throw new \Exception('Missing webhook signature');
        }

        if (!$gateway->verifyWebhookSignature($payload, $signature)) {
            throw new \Exception('Invalid webhook signature');
        }

        // Process webhook through gateway
        $gateway->handleWebhook($payload, $signature);

        // Process webhook event for our records
        $this->processWebhookEvent($gatewayName, $payload);
    }

    /**
     * Process webhook event and update transaction records
     *
     * @param string $gatewayName
     * @param array $payload
     * @return void
     */
    private function processWebhookEvent(string $gatewayName, array $payload): void
    {
        // Extract payment intent ID from payload (Stripe format)
        $paymentIntentId = $payload['data']['object']['id'] ?? $payload['payment_intent_id'] ?? null;

        if (!$paymentIntentId) {
            Log::warning('Webhook received without payment intent ID', [
                'gateway' => $gatewayName,
                'payload_keys' => array_keys($payload),
            ]);
            return;
        }

        // Find the transaction
        $transaction = PaymentTransaction::where('transaction_id', $paymentIntentId)->first();

        if (!$transaction) {
            Log::warning('Transaction not found for webhook', [
                'gateway' => $gatewayName,
                'payment_intent_id' => $paymentIntentId,
            ]);
            return;
        }

        // Determine event type (Stripe format: payment_intent.succeeded, etc.)
        $eventType = $payload['type'] ?? 'unknown';

        // Update transaction based on event type
        DB::transaction(function () use ($transaction, $eventType, $payload) {
            $previousStatus = $transaction->status;

            switch ($eventType) {
                case 'payment_intent.succeeded':
                    if ($transaction->status !== 'captured') {
                        $transaction->markAsCaptured();
                        $this->handlePaymentSuccess($transaction);
                    }
                    break;

                case 'payment_intent.payment_failed':
                    $errorMessage = $payload['data']['object']['last_payment_error']['message'] ?? 'Payment failed';
                    $transaction->markAsFailed($errorMessage);
                    break;

                case 'payment_intent.canceled':
                    $transaction->update(['status' => 'cancelled']);
                    break;

                case 'charge.refunded':
                    $transaction->markAsRefunded();
                    $this->handleRefundSuccess($transaction);
                    break;

                default:
                    Log::info('Unhandled webhook event type', [
                        'type' => $eventType,
                        'transaction_id' => $transaction->id,
                    ]);
                    return;
            }

            // Log the webhook event
            Log::info('Webhook processed successfully', [
                'event_type' => $eventType,
                'transaction_id' => $transaction->id,
                'previous_status' => $previousStatus,
                'new_status' => $transaction->fresh()->status,
            ]);

            // Store webhook data in transaction metadata
            $transaction->update([
                'gateway_response' => array_merge(
                    $transaction->gateway_response ?? [],
                    ['webhook_events' => array_merge(
                        $transaction->gateway_response['webhook_events'] ?? [],
                        [['type' => $eventType, 'received_at' => now()->toIso8601String()]]
                    )]
                ),
            ]);
        });
    }

    /**
     * Handle successful payment - complete checkout and send notifications
     *
     * @param PaymentTransaction $transaction
     * @return void
     */
    private function handlePaymentSuccess(PaymentTransaction $transaction): void
    {
        $session = $transaction->checkoutSession;

        if ($session && $session->status !== 'completed') {
            // Update checkout session
            $session->update(['status' => 'payment_confirmed']);

            // Complete checkout and create order
            $order = $this->checkoutService->completeCheckout($session);

            // Link transaction to order
            $transaction->update(['sales_order_id' => $order->id]);

            // Send notifications
            $this->notificationService->sendPaymentConfirmation($transaction->fresh());
            $this->notificationService->sendOrderConfirmation($order);
        }
    }

    /**
     * Handle successful refund - send notification
     *
     * @param PaymentTransaction $transaction
     * @return void
     */
    private function handleRefundSuccess(PaymentTransaction $transaction): void
    {
        // Send refund confirmation if order exists
        if ($transaction->salesOrder) {
            $this->notificationService->sendRefundConfirmation($transaction);
        }
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
