<?php

namespace Modules\Ecommerce\Services\Payment;

use Modules\Ecommerce\Models\CheckoutSession;
use Modules\Ecommerce\Models\PaymentTransaction;

interface PaymentGatewayInterface
{
    /**
     * Create a payment intent for the checkout session
     *
     * @param CheckoutSession $session
     * @param array $paymentData
     * @return array ['payment_intent_id' => string, 'client_secret' => string, 'status' => string]
     */
    public function createPaymentIntent(CheckoutSession $session, array $paymentData): array;

    /**
     * Capture/confirm an authorized payment
     *
     * @param string $paymentIntentId
     * @return array ['status' => string, 'transaction_id' => string]
     */
    public function capturePayment(string $paymentIntentId): array;

    /**
     * Refund a captured payment
     *
     * @param string $transactionId
     * @param float $amount
     * @param string|null $reason
     * @return array ['status' => string, 'refund_id' => string]
     */
    public function refundPayment(string $transactionId, float $amount, ?string $reason = null): array;

    /**
     * Get payment status from gateway
     *
     * @param string $paymentIntentId
     * @return string (pending|authorized|captured|failed|refunded)
     */
    public function getPaymentStatus(string $paymentIntentId): string;

    /**
     * Cancel/void a pending payment
     *
     * @param string $paymentIntentId
     * @return array ['status' => string]
     */
    public function cancelPayment(string $paymentIntentId): array;

    /**
     * Handle webhook from payment gateway
     *
     * @param array $payload
     * @param string|null $signature
     * @return void
     */
    public function handleWebhook(array $payload, ?string $signature = null): void;

    /**
     * Verify webhook signature
     *
     * @param array $payload
     * @param string $signature
     * @return bool
     */
    public function verifyWebhookSignature(array $payload, string $signature): bool;

    /**
     * Get gateway name
     *
     * @return string
     */
    public function getName(): string;
}
