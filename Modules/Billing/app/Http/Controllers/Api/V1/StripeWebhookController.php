<?php

namespace Modules\Billing\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Billing\Services\StripeService;
use Exception;

class StripeWebhookController extends Controller
{
    protected StripeService $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Handle Stripe webhook.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        try {
            $rawPayload = $request->getContent();
            $signature = $request->header('Stripe-Signature');

            if (!$signature) {
                return response()->json([
                    'error' => 'Missing Stripe signature',
                ], 400);
            }

            $this->stripeService->handleWebhookRaw($rawPayload, $signature);

            return response()->json([
                'message' => 'Webhook processed successfully',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Webhook processing failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
