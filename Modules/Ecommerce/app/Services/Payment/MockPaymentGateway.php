<?php

namespace Modules\Ecommerce\Services\Payment;

use Modules\Ecommerce\Models\CheckoutSession;
use Modules\Ecommerce\Models\PaymentTransaction;
use Illuminate\Support\Str;

class MockPaymentGateway implements PaymentGatewayInterface
{
    private bool $shouldFail = false;
    private array $paymentIntents = [];

    /**
     * Configure gateway to simulate failures
     *
     * @param bool $shouldFail
     * @return void
     */
    public function setShouldFail(bool $shouldFail): void
    {
        $this->shouldFail = $shouldFail;
    }

    /**
     * Create a payment intent for the checkout session
     *
     * @param CheckoutSession $session
     * @param array $paymentData
     * @return array
     */
    public function createPaymentIntent(CheckoutSession $session, array $paymentData): array
    {
        if ($this->shouldFail) {
            return [
                'status' => 'failed',
                'error' => 'Simulated payment failure',
            ];
        }

        $paymentIntentId = 'pi_mock_' . Str::random(24);
        $clientSecret = 'mock_secret_' . Str::random(32);

        // Store mock payment intent
        $this->paymentIntents[$paymentIntentId] = [
            'id' => $paymentIntentId,
            'amount' => $session->total_amount,
            'currency' => $session->currency,
            'status' => 'pending',
            'created_at' => now(),
            'metadata' => [
                'checkout_session_id' => $session->id,
                'payment_method' => $paymentData['payment_method'] ?? 'card',
            ],
        ];

        return [
            'payment_intent_id' => $paymentIntentId,
            'client_secret' => $clientSecret,
            'status' => 'pending',
            'amount' => $session->total_amount,
            'currency' => $session->currency,
        ];
    }

    /**
     * Capture/confirm an authorized payment
     *
     * @param string $paymentIntentId
     * @return array
     */
    public function capturePayment(string $paymentIntentId): array
    {
        if ($this->shouldFail) {
            return [
                'status' => 'failed',
                'error' => 'Simulated capture failure',
            ];
        }

        if (!isset($this->paymentIntents[$paymentIntentId])) {
            return [
                'status' => 'failed',
                'error' => 'Payment intent not found',
            ];
        }

        // Update status
        $this->paymentIntents[$paymentIntentId]['status'] = 'captured';
        $this->paymentIntents[$paymentIntentId]['captured_at'] = now();

        $transactionId = 'txn_mock_' . Str::random(24);

        return [
            'status' => 'captured',
            'transaction_id' => $transactionId,
            'amount' => $this->paymentIntents[$paymentIntentId]['amount'],
            'currency' => $this->paymentIntents[$paymentIntentId]['currency'],
            'captured_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Refund a captured payment
     *
     * @param string $transactionId
     * @param float $amount
     * @param string|null $reason
     * @return array
     */
    public function refundPayment(string $transactionId, float $amount, ?string $reason = null): array
    {
        if ($this->shouldFail) {
            return [
                'status' => 'failed',
                'error' => 'Simulated refund failure',
            ];
        }

        $refundId = 'ref_mock_' . Str::random(24);

        return [
            'status' => 'refunded',
            'refund_id' => $refundId,
            'amount' => $amount,
            'reason' => $reason ?? 'Customer requested',
            'refunded_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get payment status from gateway
     *
     * @param string $paymentIntentId
     * @return string
     */
    public function getPaymentStatus(string $paymentIntentId): string
    {
        if (!isset($this->paymentIntents[$paymentIntentId])) {
            return 'failed';
        }

        return $this->paymentIntents[$paymentIntentId]['status'];
    }

    /**
     * Cancel/void a pending payment
     *
     * @param string $paymentIntentId
     * @return array
     */
    public function cancelPayment(string $paymentIntentId): array
    {
        if (!isset($this->paymentIntents[$paymentIntentId])) {
            return [
                'status' => 'failed',
                'error' => 'Payment intent not found',
            ];
        }

        $this->paymentIntents[$paymentIntentId]['status'] = 'cancelled';

        return [
            'status' => 'cancelled',
            'cancelled_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Handle webhook from payment gateway
     *
     * @param array $payload
     * @param string|null $signature
     * @return void
     */
    public function handleWebhook(array $payload, ?string $signature = null): void
    {
        // Mock webhook handling - in real implementation this would process events
        logger()->info('Mock webhook received', $payload);
    }

    /**
     * Verify webhook signature
     *
     * @param array $payload
     * @param string $signature
     * @return bool
     */
    public function verifyWebhookSignature(array $payload, string $signature): bool
    {
        // Mock verification - always returns true
        return $signature === 'mock_signature';
    }

    /**
     * Get gateway name
     *
     * @return string
     */
    public function getName(): string
    {
        return 'mock';
    }

    /**
     * Get all stored payment intents (for testing)
     *
     * @return array
     */
    public function getPaymentIntents(): array
    {
        return $this->paymentIntents;
    }

    /**
     * Clear all stored payment intents (for testing)
     *
     * @return void
     */
    public function clearPaymentIntents(): void
    {
        $this->paymentIntents = [];
    }
}
