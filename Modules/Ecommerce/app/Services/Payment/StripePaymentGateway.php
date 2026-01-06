<?php

namespace Modules\Ecommerce\Services\Payment;

use Modules\Ecommerce\Models\CheckoutSession;
use Modules\Billing\Services\StripeService;
use Illuminate\Support\Facades\Log;

/**
 * Stripe Payment Gateway Adapter
 *
 * Adapts StripeService from Billing module to PaymentGatewayInterface
 * for use in Ecommerce checkout flow.
 */
class StripePaymentGateway implements PaymentGatewayInterface
{
    private StripeService $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * {@inheritdoc}
     */
    public function createPaymentIntent(CheckoutSession $session, array $paymentData): array
    {
        try {
            $metadata = [
                'checkout_session_id' => $session->id,
                'user_id' => $session->user_id,
                'cart_id' => $session->shopping_cart_id,
            ];

            $options = [];
            if (isset($paymentData['customer_id'])) {
                $options['customer'] = $paymentData['customer_id'];
            }

            $paymentIntent = $this->stripeService->createPaymentIntent(
                amount: $session->total_amount,
                currency: strtolower($session->currency ?? 'mxn'),
                metadata: $metadata,
                options: $options
            );

            return [
                'payment_intent_id' => $paymentIntent->id,
                'client_secret' => $paymentIntent->client_secret,
                'status' => $this->mapStripeStatus($paymentIntent->status),
            ];
        } catch (\Exception $e) {
            Log::error('Stripe createPaymentIntent failed', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'error' => $e->getMessage(),
                'status' => 'failed',
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function capturePayment(string $paymentIntentId): array
    {
        try {
            $paymentIntent = $this->stripeService->capturePaymentIntent($paymentIntentId);

            return [
                'status' => $this->mapStripeStatus($paymentIntent->status),
                'transaction_id' => $paymentIntent->charges->data[0]->id ?? $paymentIntentId,
            ];
        } catch (\Exception $e) {
            Log::error('Stripe capturePayment failed', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function refundPayment(string $transactionId, float $amount, ?string $reason = null): array
    {
        try {
            // Convert amount to cents for Stripe
            $amountInCents = (int) ($amount * 100);

            $refund = $this->stripeService->createRefund(
                paymentIntentId: $transactionId,
                amount: $amountInCents,
                reason: $reason ? $this->mapRefundReason($reason) : null
            );

            return [
                'status' => $refund->status === 'succeeded' ? 'refunded' : 'failed',
                'refund_id' => $refund->id,
            ];
        } catch (\Exception $e) {
            Log::error('Stripe refundPayment failed', [
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentStatus(string $paymentIntentId): string
    {
        try {
            $paymentIntent = $this->stripeService->retrievePaymentIntent($paymentIntentId);
            return $this->mapStripeStatus($paymentIntent->status);
        } catch (\Exception $e) {
            Log::error('Stripe getPaymentStatus failed', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
            ]);
            return 'failed';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function cancelPayment(string $paymentIntentId): array
    {
        try {
            $paymentIntent = $this->stripeService->cancelPaymentIntent($paymentIntentId);

            return [
                'status' => $paymentIntent->status === 'canceled' ? 'cancelled' : 'failed',
            ];
        } catch (\Exception $e) {
            Log::error('Stripe cancelPayment failed', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function handleWebhook(array $payload, ?string $signature = null): void
    {
        try {
            $this->stripeService->handleWebhook($payload, $signature ?? '');
        } catch (\Exception $e) {
            Log::error('Stripe webhook handling failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function verifyWebhookSignature(array $payload, string $signature): bool
    {
        // Stripe signature verification is done inside handleWebhook
        // This is a pre-check, actual verification happens in StripeService
        return !empty($signature) && !empty(config('services.stripe.webhook_secret'));
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'stripe';
    }

    /**
     * Map Stripe status to internal status
     */
    private function mapStripeStatus(string $stripeStatus): string
    {
        return match ($stripeStatus) {
            'requires_payment_method',
            'requires_confirmation',
            'requires_action' => 'pending',
            'processing' => 'authorized',
            'requires_capture' => 'authorized',
            'succeeded' => 'captured',
            'canceled' => 'cancelled',
            default => 'failed',
        };
    }

    /**
     * Map refund reason to Stripe reason
     */
    private function mapRefundReason(string $reason): string
    {
        $reasonMap = [
            'duplicate' => 'duplicate',
            'fraud' => 'fraudulent',
            'customer_request' => 'requested_by_customer',
        ];

        return $reasonMap[$reason] ?? 'requested_by_customer';
    }
}
