<?php

namespace Modules\Billing\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Billing\Services\StripeService;
use Modules\Billing\Models\PaymentTransaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;

/**
 * Stripe Payment Intents Controller
 *
 * Exposes HTTP endpoints for Stripe PaymentIntent operations.
 * Uses StripeService for all Stripe API interactions.
 *
 * @group Stripe Payments
 */
class StripeController extends Controller
{
    public function __construct(
        private readonly StripeService $stripeService
    ) {}

    /**
     * Create Payment Intent
     *
     * Creates a new Stripe PaymentIntent for the specified amount.
     * Returns client_secret for frontend to complete payment.
     *
     * @bodyParam amount number required Amount in currency units (e.g., 100.00 for $100). Example: 1500.00
     * @bodyParam currency string Currency code (default: mxn). Example: mxn
     * @bodyParam metadata object Optional metadata to attach. Example: {"order_id": "12345"}
     * @bodyParam customer_id string Optional Stripe customer ID. Example: cus_ABC123
     * @bodyParam capture_method string Capture method: automatic or manual. Example: automatic
     *
     * @response 201 {
     *   "data": {
     *     "id": "pi_ABC123",
     *     "client_secret": "pi_ABC123_secret_XYZ",
     *     "status": "requires_payment_method",
     *     "amount": 150000,
     *     "currency": "mxn"
     *   }
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:10',
            'currency' => 'nullable|string|size:3',
            'metadata' => 'nullable|array',
            'customer_id' => 'nullable|string',
            'capture_method' => 'nullable|in:automatic,manual',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $options = [];

            if ($request->has('customer_id')) {
                $options['customer'] = $request->input('customer_id');
            }

            if ($request->input('capture_method') === 'manual') {
                $options['capture_method'] = 'manual';
            }

            $paymentIntent = $this->stripeService->createPaymentIntent(
                amount: $request->input('amount'),
                currency: $request->input('currency', 'mxn'),
                metadata: $request->input('metadata', []),
                options: $options
            );

            // Sync to local PaymentTransaction record
            $transaction = $this->stripeService->syncPaymentIntentToTransaction($paymentIntent);

            return response()->json([
                'data' => [
                    'id' => $paymentIntent->id,
                    'client_secret' => $paymentIntent->client_secret,
                    'status' => $paymentIntent->status,
                    'amount' => $paymentIntent->amount,
                    'currency' => $paymentIntent->currency,
                    'transaction_id' => $transaction->id,
                ],
            ], 201);
        } catch (Exception $e) {
            Log::error('StripeController@store failed', [
                'error' => $e->getMessage(),
                'amount' => $request->input('amount'),
            ]);

            return response()->json([
                'error' => 'Failed to create payment intent',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Payment Intent
     *
     * Retrieves the current state of a PaymentIntent.
     *
     * @urlParam id string required The PaymentIntent ID. Example: pi_ABC123
     *
     * @response 200 {
     *   "data": {
     *     "id": "pi_ABC123",
     *     "status": "succeeded",
     *     "amount": 150000,
     *     "currency": "mxn",
     *     "payment_method": "pm_XYZ",
     *     "created": 1704067200
     *   }
     * }
     */
    public function show(string $id): JsonResponse
    {
        try {
            $paymentIntent = $this->stripeService->retrievePaymentIntent($id);

            return response()->json([
                'data' => [
                    'id' => $paymentIntent->id,
                    'status' => $paymentIntent->status,
                    'amount' => $paymentIntent->amount,
                    'currency' => $paymentIntent->currency,
                    'payment_method' => $paymentIntent->payment_method,
                    'capture_method' => $paymentIntent->capture_method,
                    'client_secret' => $paymentIntent->client_secret,
                    'created' => $paymentIntent->created,
                    'metadata' => $paymentIntent->metadata->toArray(),
                ],
            ]);
        } catch (Exception $e) {
            Log::error('StripeController@show failed', [
                'error' => $e->getMessage(),
                'payment_intent_id' => $id,
            ]);

            return response()->json([
                'error' => 'Payment intent not found',
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Confirm Payment Intent
     *
     * Confirms a PaymentIntent with a payment method.
     * Use this when you need server-side confirmation.
     *
     * @urlParam id string required The PaymentIntent ID. Example: pi_ABC123
     * @bodyParam payment_method string The payment method ID. Example: pm_XYZ789
     * @bodyParam return_url string Return URL for redirect-based payment methods. Example: https://example.com/return
     *
     * @response 200 {
     *   "data": {
     *     "id": "pi_ABC123",
     *     "status": "succeeded",
     *     "next_action": null
     *   }
     * }
     */
    public function confirm(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'payment_method' => 'nullable|string',
            'return_url' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $options = [];

            if ($request->has('payment_method')) {
                $options['payment_method'] = $request->input('payment_method');
            }

            if ($request->has('return_url')) {
                $options['return_url'] = $request->input('return_url');
            }

            $paymentIntent = $this->stripeService->confirmPaymentIntent($id, $options);

            // Sync updated status to local record
            $this->stripeService->syncPaymentIntentToTransaction($paymentIntent);

            return response()->json([
                'data' => [
                    'id' => $paymentIntent->id,
                    'status' => $paymentIntent->status,
                    'next_action' => $paymentIntent->next_action,
                ],
            ]);
        } catch (Exception $e) {
            Log::error('StripeController@confirm failed', [
                'error' => $e->getMessage(),
                'payment_intent_id' => $id,
            ]);

            return response()->json([
                'error' => 'Failed to confirm payment intent',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Capture Payment Intent
     *
     * Captures a PaymentIntent that was created with capture_method=manual.
     * Only works on PaymentIntents with status "requires_capture".
     *
     * @urlParam id string required The PaymentIntent ID. Example: pi_ABC123
     * @bodyParam amount_to_capture number Optional amount to capture (for partial capture). Example: 100.00
     *
     * @response 200 {
     *   "data": {
     *     "id": "pi_ABC123",
     *     "status": "succeeded",
     *     "amount_captured": 150000
     *   }
     * }
     */
    public function capture(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount_to_capture' => 'nullable|numeric|min:0.50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $amountInCents = null;
            if ($request->has('amount_to_capture')) {
                $amountInCents = (int) ($request->input('amount_to_capture') * 100);
            }

            $paymentIntent = $this->stripeService->capturePaymentIntent($id, $amountInCents);

            // Sync updated status to local record
            $this->stripeService->syncPaymentIntentToTransaction($paymentIntent);

            return response()->json([
                'data' => [
                    'id' => $paymentIntent->id,
                    'status' => $paymentIntent->status,
                    'amount_captured' => $paymentIntent->amount_received,
                ],
            ]);
        } catch (Exception $e) {
            Log::error('StripeController@capture failed', [
                'error' => $e->getMessage(),
                'payment_intent_id' => $id,
            ]);

            return response()->json([
                'error' => 'Failed to capture payment intent',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Cancel Payment Intent
     *
     * Cancels a PaymentIntent. This cannot be undone.
     * Only works on PaymentIntents that have not been captured.
     *
     * @urlParam id string required The PaymentIntent ID. Example: pi_ABC123
     *
     * @response 200 {
     *   "data": {
     *     "id": "pi_ABC123",
     *     "status": "canceled"
     *   }
     * }
     */
    public function cancel(string $id): JsonResponse
    {
        try {
            $paymentIntent = $this->stripeService->cancelPaymentIntent($id);

            // Sync updated status to local record
            $this->stripeService->syncPaymentIntentToTransaction($paymentIntent);

            return response()->json([
                'data' => [
                    'id' => $paymentIntent->id,
                    'status' => $paymentIntent->status,
                ],
            ]);
        } catch (Exception $e) {
            Log::error('StripeController@cancel failed', [
                'error' => $e->getMessage(),
                'payment_intent_id' => $id,
            ]);

            return response()->json([
                'error' => 'Failed to cancel payment intent',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Create Refund
     *
     * Creates a refund for a captured PaymentIntent.
     * Can do full or partial refunds.
     *
     * @bodyParam payment_intent_id string required The PaymentIntent ID to refund. Example: pi_ABC123
     * @bodyParam amount number Optional amount to refund (for partial refund). Example: 50.00
     * @bodyParam reason string Reason for refund: duplicate, fraudulent, requested_by_customer. Example: requested_by_customer
     *
     * @response 201 {
     *   "data": {
     *     "id": "re_ABC123",
     *     "status": "succeeded",
     *     "amount": 5000,
     *     "payment_intent": "pi_ABC123"
     *   }
     * }
     */
    public function refund(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'payment_intent_id' => 'required|string',
            'amount' => 'nullable|numeric|min:0.50',
            'reason' => 'nullable|in:duplicate,fraudulent,requested_by_customer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $amountInCents = null;
            if ($request->has('amount')) {
                $amountInCents = (int) ($request->input('amount') * 100);
            }

            $refund = $this->stripeService->createRefund(
                paymentIntentId: $request->input('payment_intent_id'),
                amount: $amountInCents,
                reason: $request->input('reason')
            );

            // Update local PaymentTransaction to reflect refund
            $transaction = PaymentTransaction::where('payment_intent_id', $request->input('payment_intent_id'))->first();
            if ($transaction) {
                $transaction->update([
                    'status' => $refund->status === 'succeeded' ? 'refunded' : $transaction->status,
                    'refunded_at' => $refund->status === 'succeeded' ? now() : null,
                    'metadata' => array_merge($transaction->metadata ?? [], [
                        'refund_id' => $refund->id,
                        'refund_amount' => $refund->amount,
                        'refund_reason' => $refund->reason,
                    ]),
                ]);
            }

            return response()->json([
                'data' => [
                    'id' => $refund->id,
                    'status' => $refund->status,
                    'amount' => $refund->amount,
                    'currency' => $refund->currency,
                    'payment_intent' => $refund->payment_intent,
                ],
            ], 201);
        } catch (Exception $e) {
            Log::error('StripeController@refund failed', [
                'error' => $e->getMessage(),
                'payment_intent_id' => $request->input('payment_intent_id'),
            ]);

            return response()->json([
                'error' => 'Failed to create refund',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
