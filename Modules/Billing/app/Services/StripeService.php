<?php

namespace Modules\Billing\Services;

use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\Customer as StripeCustomer;
use Modules\Billing\Models\PaymentTransaction;
use Illuminate\Support\Facades\Log;
use Exception;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create a Payment Intent.
     *
     * @param float $amount Amount in currency (e.g., 100.00 for $100.00)
     * @param string $currency Currency code (e.g., 'mxn', 'usd')
     * @param array $metadata Additional metadata
     * @param array $options Additional options (customer, payment_method, etc.)
     * @return PaymentIntent
     * @throws Exception
     */
    public function createPaymentIntent(
        float $amount,
        string $currency = 'mxn',
        array $metadata = [],
        array $options = []
    ): PaymentIntent {
        try {
            // Convert amount to cents (Stripe expects smallest currency unit)
            $amountInCents = (int) round($amount * 100);

            $params = [
                'amount' => $amountInCents,
                'currency' => strtolower($currency),
                'metadata' => $metadata,
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ];

            // Merge additional options
            $params = array_merge($params, $options);

            $paymentIntent = PaymentIntent::create($params);

            Log::info('Stripe Payment Intent created', [
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $amount,
                'currency' => $currency,
            ]);

            return $paymentIntent;
        } catch (Exception $e) {
            Log::error('Stripe Payment Intent creation failed', [
                'error' => $e->getMessage(),
                'amount' => $amount,
                'currency' => $currency,
            ]);

            throw $e;
        }
    }

    /**
     * Retrieve a Payment Intent.
     *
     * @param string $paymentIntentId
     * @return PaymentIntent
     * @throws Exception
     */
    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent
    {
        try {
            return PaymentIntent::retrieve($paymentIntentId);
        } catch (Exception $e) {
            Log::error('Stripe Payment Intent retrieval failed', [
                'error' => $e->getMessage(),
                'payment_intent_id' => $paymentIntentId,
            ]);

            throw $e;
        }
    }

    /**
     * Confirm a Payment Intent.
     *
     * @param string $paymentIntentId
     * @param array $options Additional options (payment_method, return_url, etc.)
     * @return PaymentIntent
     * @throws Exception
     */
    public function confirmPaymentIntent(string $paymentIntentId, array $options = []): PaymentIntent
    {
        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
            $confirmedIntent = $paymentIntent->confirm($options);

            Log::info('Stripe Payment Intent confirmed', [
                'payment_intent_id' => $paymentIntentId,
                'status' => $confirmedIntent->status,
            ]);

            return $confirmedIntent;
        } catch (Exception $e) {
            Log::error('Stripe Payment Intent confirmation failed', [
                'error' => $e->getMessage(),
                'payment_intent_id' => $paymentIntentId,
            ]);

            throw $e;
        }
    }

    /**
     * Cancel a Payment Intent.
     *
     * @param string $paymentIntentId
     * @return PaymentIntent
     * @throws Exception
     */
    public function cancelPaymentIntent(string $paymentIntentId): PaymentIntent
    {
        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
            $canceledIntent = $paymentIntent->cancel();

            Log::info('Stripe Payment Intent canceled', [
                'payment_intent_id' => $paymentIntentId,
            ]);

            return $canceledIntent;
        } catch (Exception $e) {
            Log::error('Stripe Payment Intent cancellation failed', [
                'error' => $e->getMessage(),
                'payment_intent_id' => $paymentIntentId,
            ]);

            throw $e;
        }
    }

    /**
     * Capture a Payment Intent (for manual capture mode).
     *
     * @param string $paymentIntentId
     * @param int|null $amountToCapture Amount in cents (null to capture full amount)
     * @return PaymentIntent
     * @throws Exception
     */
    public function capturePaymentIntent(string $paymentIntentId, ?int $amountToCapture = null): PaymentIntent
    {
        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);

            $params = [];
            if ($amountToCapture !== null) {
                $params['amount_to_capture'] = $amountToCapture;
            }

            $capturedIntent = $paymentIntent->capture($params);

            Log::info('Stripe Payment Intent captured', [
                'payment_intent_id' => $paymentIntentId,
                'amount_captured' => $amountToCapture,
            ]);

            return $capturedIntent;
        } catch (Exception $e) {
            Log::error('Stripe Payment Intent capture failed', [
                'error' => $e->getMessage(),
                'payment_intent_id' => $paymentIntentId,
            ]);

            throw $e;
        }
    }

    /**
     * Create a Refund.
     *
     * @param string $paymentIntentId
     * @param int|null $amount Amount in cents (null for full refund)
     * @param string|null $reason Reason for refund ('duplicate', 'fraudulent', 'requested_by_customer')
     * @return Refund
     * @throws Exception
     */
    public function createRefund(
        string $paymentIntentId,
        ?int $amount = null,
        ?string $reason = null
    ): Refund {
        try {
            $params = [
                'payment_intent' => $paymentIntentId,
            ];

            if ($amount !== null) {
                $params['amount'] = $amount;
            }

            if ($reason !== null) {
                $params['reason'] = $reason;
            }

            $refund = Refund::create($params);

            Log::info('Stripe Refund created', [
                'refund_id' => $refund->id,
                'payment_intent_id' => $paymentIntentId,
                'amount' => $amount,
                'reason' => $reason,
            ]);

            return $refund;
        } catch (Exception $e) {
            Log::error('Stripe Refund creation failed', [
                'error' => $e->getMessage(),
                'payment_intent_id' => $paymentIntentId,
            ]);

            throw $e;
        }
    }

    /**
     * Create a Stripe Customer.
     *
     * @param string $email
     * @param array $metadata
     * @param array $options Additional options (name, phone, etc.)
     * @return StripeCustomer
     * @throws Exception
     */
    public function createCustomer(string $email, array $metadata = [], array $options = []): StripeCustomer
    {
        try {
            $params = [
                'email' => $email,
                'metadata' => $metadata,
            ];

            $params = array_merge($params, $options);

            $customer = StripeCustomer::create($params);

            Log::info('Stripe Customer created', [
                'customer_id' => $customer->id,
                'email' => $email,
            ]);

            return $customer;
        } catch (Exception $e) {
            Log::error('Stripe Customer creation failed', [
                'error' => $e->getMessage(),
                'email' => $email,
            ]);

            throw $e;
        }
    }

    /**
     * Sync Payment Intent with PaymentTransaction.
     *
     * @param PaymentIntent $paymentIntent
     * @param PaymentTransaction|null $transaction
     * @return PaymentTransaction
     */
    public function syncPaymentIntentToTransaction(
        PaymentIntent $paymentIntent,
        ?PaymentTransaction $transaction = null
    ): PaymentTransaction {
        if ($transaction === null) {
            // Find existing or create new
            $transaction = PaymentTransaction::firstOrNew([
                'payment_intent_id' => $paymentIntent->id,
            ]);
        }

        // Map Stripe status to our status
        $status = $this->mapStripeStatus($paymentIntent->status);

        // Convert amount from cents to decimal
        $amount = $paymentIntent->amount / 100;

        $transaction->fill([
            'gateway' => 'stripe',
            'payment_intent_id' => $paymentIntent->id,
            'transaction_id' => $paymentIntent->charges->data[0]->id ?? null,
            'client_secret' => $paymentIntent->client_secret,
            'amount' => $amount,
            'currency' => strtoupper($paymentIntent->currency),
            'status' => $status,
            'payment_method' => $paymentIntent->payment_method_types[0] ?? null,
            'gateway_response' => collect($paymentIntent->toArray())->except(['client_secret'])->all(),
        ]);

        // Set card info if available
        if (isset($paymentIntent->charges->data[0]->payment_method_details->card)) {
            $card = $paymentIntent->charges->data[0]->payment_method_details->card;
            $transaction->card_brand = $card->brand ?? null;
            $transaction->card_last4 = $card->last4 ?? null;
        }

        // Link transaction to the sales order when the intent carries order_id
        // metadata (checkout creates the order before the payment intent)
        $orderId = $paymentIntent->metadata['order_id'] ?? null;
        if ($orderId && !$transaction->sales_order_id
            && \Modules\Sales\Models\SalesOrder::whereKey($orderId)->exists()) {
            $transaction->sales_order_id = (int) $orderId;
        }

        // Set event timestamps
        if ($paymentIntent->status === 'succeeded') {
            $transaction->captured_at = now();
        } elseif ($paymentIntent->status === 'canceled') {
            $transaction->failed_at = now();
        }

        $transaction->save();

        Log::info('Payment Intent synced to transaction', [
            'transaction_id' => $transaction->id,
            'payment_intent_id' => $paymentIntent->id,
            'status' => $status,
        ]);

        return $transaction;
    }

    /**
     * Map Stripe status to our internal status.
     *
     * @param string $stripeStatus
     * @return string
     */
    protected function mapStripeStatus(string $stripeStatus): string
    {
        return match ($stripeStatus) {
            'requires_payment_method',
            'requires_confirmation',
            'requires_action' => 'pending',
            'processing' => 'authorized',
            'succeeded' => 'captured',
            'canceled' => 'cancelled',
            default => 'failed',
        };
    }

    /**
     * Handle webhook event using raw body for correct HMAC verification.
     *
     * @param string $rawBody
     * @param string $signature
     * @return bool
     * @throws Exception
     */
    public function handleWebhookRaw(string $rawBody, string $signature): bool
    {
        return $this->processWebhookEvent($rawBody, $signature);
    }

    /**
     * Handle webhook event.
     *
     * @param array $payload
     * @param string $signature
     * @return bool
     * @throws Exception
     */
    public function handleWebhook(array $payload, string $signature): bool
    {
        // Re-encoding loses original formatting; prefer handleWebhookRaw
        return $this->processWebhookEvent(json_encode($payload), $signature);
    }

    /**
     * Process webhook event (shared logic).
     *
     * @param string $body
     * @param string $signature
     * @return bool
     * @throws Exception
     */
    private function processWebhookEvent(string $body, string $signature): bool
    {
        try {
            $webhookSecret = config('services.stripe.webhook_secret');

            if (!$webhookSecret) {
                throw new Exception('Stripe webhook secret not configured');
            }

            // Verify webhook signature using raw body
            $event = \Stripe\Webhook::constructEvent(
                $body,
                $signature,
                $webhookSecret
            );

            Log::info('Stripe webhook received', [
                'event_type' => $event->type,
                'event_id' => $event->id,
            ]);

            // Handle different event types
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $this->handlePaymentIntentSucceeded($event->data->object);
                    break;

                case 'payment_intent.payment_failed':
                    $this->handlePaymentIntentFailed($event->data->object);
                    break;

                case 'charge.refunded':
                    $this->handleChargeRefunded($event->data->object);
                    break;

                default:
                    Log::info('Unhandled webhook event type', ['type' => $event->type]);
            }

            return true;
        } catch (Exception $e) {
            Log::error('Stripe webhook handling failed', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle payment_intent.succeeded event.
     *
     * @param object $paymentIntent
     * @return void
     */
    protected function handlePaymentIntentSucceeded($paymentIntent): void
    {
        $transaction = PaymentTransaction::where('payment_intent_id', $paymentIntent->id)->first();

        if ($transaction) {
            $transaction->update([
                'status' => 'captured',
                'captured_at' => now(),
                'transaction_id' => $paymentIntent->charges->data[0]->id ?? null,
                'gateway_response' => (array) $paymentIntent,
            ]);

            Log::info('Payment Intent succeeded - transaction updated', [
                'transaction_id' => $transaction->id,
                'payment_intent_id' => $paymentIntent->id,
            ]);

            // Dispatch PaymentCaptured event for CFDI auto-generation
            event(new \Modules\Billing\Events\PaymentCaptured($transaction));
        }
    }

    /**
     * Handle payment_intent.payment_failed event.
     *
     * @param object $paymentIntent
     * @return void
     */
    protected function handlePaymentIntentFailed($paymentIntent): void
    {
        $transaction = PaymentTransaction::where('payment_intent_id', $paymentIntent->id)->first();

        if ($transaction) {
            $transaction->update([
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => $paymentIntent->last_payment_error->message ?? 'Payment failed',
                'gateway_response' => (array) $paymentIntent,
            ]);

            Log::info('Payment Intent failed - transaction updated', [
                'transaction_id' => $transaction->id,
                'payment_intent_id' => $paymentIntent->id,
            ]);
        }
    }

    /**
     * Handle charge.refunded event.
     *
     * @param object $charge
     * @return void
     */
    protected function handleChargeRefunded($charge): void
    {
        $transaction = PaymentTransaction::where('transaction_id', $charge->id)->first();

        if ($transaction) {
            $transaction->update([
                'status' => 'refunded',
                'refunded_at' => now(),
                'gateway_response' => (array) $charge,
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'refund_id' => $charge->refunds->data[0]->id ?? null,
                    'refund_reason' => $charge->refunds->data[0]->reason ?? null,
                ]),
            ]);

            Log::info('Charge refunded - transaction updated', [
                'transaction_id' => $transaction->id,
                'charge_id' => $charge->id,
            ]);
        }
    }
}
