<?php

namespace Modules\Ecommerce\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Ecommerce\Models\CheckoutSession;
use Modules\Ecommerce\Models\PaymentTransaction;
use Modules\Ecommerce\Services\Payment\PaymentService;

class PaymentController extends Controller
{
    private PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
        $this->middleware('auth:sanctum')->except(['webhook']);
    }

    /**
     * Get available payment gateways
     *
     * GET /api/v1/payment/gateways
     *
     * @return JsonResponse
     */
    public function gateways(): JsonResponse
    {
        $gateways = $this->paymentService->getAvailableGateways();

        return response()->json([
            'data' => $gateways,
        ]);
    }

    /**
     * Process payment for checkout session
     *
     * POST /api/v1/checkout/{session}/payment
     *
     * @param Request $request
     * @param CheckoutSession $session
     * @return JsonResponse
     */
    public function process(Request $request, CheckoutSession $session): JsonResponse
    {
        $request->validate([
            'gateway' => 'required|string|in:mock,stripe',
            'payment_method' => 'required|string|in:card,bank_transfer,paypal',
            'payment_data' => 'sometimes|array',
        ]);

        // Verify session belongs to user
        if ($session->user_id && $session->user_id !== auth()->id()) {
            return response()->json([
                'error' => 'Unauthorized access to checkout session',
            ], 403);
        }

        try {
            $transaction = $this->paymentService->processPayment(
                $session,
                $request->gateway,
                array_merge(
                    ['payment_method' => $request->payment_method],
                    $request->payment_data ?? []
                )
            );

            return response()->json([
                'data' => $transaction->load('checkoutSession'),
                'message' => 'Payment initiated successfully',
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Payment processing failed',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Confirm/capture payment
     *
     * POST /api/v1/payment/{transaction}/confirm
     *
     * @param PaymentTransaction $transaction
     * @return JsonResponse
     */
    public function confirm(PaymentTransaction $transaction): JsonResponse
    {
        // Verify transaction belongs to user's session
        $session = $transaction->checkoutSession;
        if ($session && $session->user_id && $session->user_id !== auth()->id()) {
            return response()->json([
                'error' => 'Unauthorized access to payment transaction',
            ], 403);
        }

        try {
            $confirmedTransaction = $this->paymentService->confirmPayment($transaction);

            return response()->json([
                'data' => $confirmedTransaction->load(['checkoutSession', 'salesOrder']),
                'message' => 'Payment confirmed and order created successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Payment confirmation failed',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get payment status
     *
     * GET /api/v1/checkout/{session}/payment-status
     *
     * @param CheckoutSession $session
     * @return JsonResponse
     */
    public function status(CheckoutSession $session): JsonResponse
    {
        // Verify session belongs to user
        if ($session->user_id && $session->user_id !== auth()->id()) {
            return response()->json([
                'error' => 'Unauthorized access to checkout session',
            ], 403);
        }

        $transactions = $session->paymentTransactions()->latest()->get();

        return response()->json([
            'data' => [
                'session_status' => $session->status,
                'payment_status' => $session->status,
                'transactions' => $transactions,
                'latest_transaction' => $transactions->first(),
            ],
        ]);
    }

    /**
     * Refund payment
     *
     * POST /api/v1/payment/{transaction}/refund
     *
     * @param Request $request
     * @param PaymentTransaction $transaction
     * @return JsonResponse
     */
    public function refund(Request $request, PaymentTransaction $transaction): JsonResponse
    {
        $request->validate([
            'amount' => 'sometimes|numeric|min:0.01',
            'reason' => 'sometimes|string|max:500',
        ]);

        try {
            $amount = $request->amount ?? $transaction->amount;
            $reason = $request->reason ?? 'Customer requested refund';

            $refundedTransaction = $this->paymentService->refundPayment(
                $transaction,
                $amount,
                $reason
            );

            return response()->json([
                'data' => $refundedTransaction,
                'message' => 'Payment refunded successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Refund failed',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Cancel pending payment
     *
     * POST /api/v1/payment/{transaction}/cancel
     *
     * @param PaymentTransaction $transaction
     * @return JsonResponse
     */
    public function cancel(PaymentTransaction $transaction): JsonResponse
    {
        // Verify transaction belongs to user's session
        $session = $transaction->checkoutSession;
        if ($session && $session->user_id && $session->user_id !== auth()->id()) {
            return response()->json([
                'error' => 'Unauthorized access to payment transaction',
            ], 403);
        }

        try {
            $this->paymentService->cancelPayment($transaction);

            return response()->json([
                'message' => 'Payment cancelled successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Payment cancellation failed',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Handle payment gateway webhook
     *
     * POST /api/v1/webhooks/payment/{gateway}
     *
     * @param Request $request
     * @param string $gateway
     * @return JsonResponse
     */
    public function webhook(Request $request, string $gateway): JsonResponse
    {
        try {
            $signature = $request->header('Stripe-Signature') // Stripe
                ?? $request->header('X-Webhook-Signature'); // Generic

            $this->paymentService->handleWebhook(
                $gateway,
                $request->all(),
                $signature
            );

            return response()->json([
                'message' => 'Webhook processed successfully',
            ]);

        } catch (\Exception $e) {
            logger()->error('Payment webhook error', [
                'gateway' => $gateway,
                'error' => $e->getMessage(),
                'payload' => $request->all(),
            ]);

            return response()->json([
                'error' => 'Webhook processing failed',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
