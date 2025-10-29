<?php

namespace Modules\Ecommerce\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Ecommerce\Models\ShoppingCart;
use Modules\Ecommerce\Models\CheckoutSession;
use Modules\Ecommerce\Services\CheckoutService;

class CheckoutController extends Controller
{
    private CheckoutService $checkoutService;

    public function __construct(CheckoutService $checkoutService)
    {
        $this->checkoutService = $checkoutService;
        $this->middleware('auth:sanctum');
    }

    /**
     * Initiate checkout from shopping cart
     *
     * POST /api/v1/checkout/initiate
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function initiate(Request $request): JsonResponse
    {
        $request->validate([
            'shopping_cart_id' => 'required|exists:shopping_carts,id',
            'email' => 'sometimes|email',
            'phone' => 'sometimes|string',
        ]);

        try {
            $cart = ShoppingCart::findOrFail($request->shopping_cart_id);

            // Verify cart belongs to user
            if ($cart->user_id && $cart->user_id !== auth()->id()) {
                return response()->json([
                    'error' => 'Unauthorized access to cart',
                ], 403);
            }

            $session = $this->checkoutService->initiateCheckout($cart, $request->only(['email', 'phone']));

            return response()->json([
                'data' => $session->load(['shoppingCart.cartItems.product', 'user']),
                'message' => 'Checkout initiated successfully',
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to initiate checkout',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get checkout session details
     *
     * GET /api/v1/checkout/{session}
     *
     * @param CheckoutSession $session
     * @return JsonResponse
     */
    public function show(CheckoutSession $session): JsonResponse
    {
        // Verify session belongs to user
        if ($session->user_id && $session->user_id !== auth()->id()) {
            return response()->json([
                'error' => 'Unauthorized access to checkout session',
            ], 403);
        }

        return response()->json([
            'data' => $session->load([
                'shoppingCart.cartItems.product',
                'shippingMethod',
                'inventoryReservations',
                'paymentTransactions',
            ]),
        ]);
    }

    /**
     * Update shipping address
     *
     * PUT /api/v1/checkout/{session}/address
     *
     * @param Request $request
     * @param CheckoutSession $session
     * @return JsonResponse
     */
    public function updateAddress(Request $request, CheckoutSession $session): JsonResponse
    {
        $request->validate([
            'shipping_address' => 'required|array',
            'shipping_address.street' => 'required|string',
            'shipping_address.city' => 'required|string',
            'shipping_address.state' => 'required|string',
            'shipping_address.postal_code' => 'required|string',
            'shipping_address.country' => 'required|string',
            'billing_address' => 'sometimes|array',
        ]);

        // Verify session belongs to user
        if ($session->user_id && $session->user_id !== auth()->id()) {
            return response()->json([
                'error' => 'Unauthorized access to checkout session',
            ], 403);
        }

        try {
            $updatedSession = $this->checkoutService->updateShippingAddress(
                $session,
                $request->shipping_address,
                $request->billing_address
            );

            return response()->json([
                'data' => $updatedSession,
                'message' => 'Address updated successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update address',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Select shipping method
     *
     * PUT /api/v1/checkout/{session}/shipping
     *
     * @param Request $request
     * @param CheckoutSession $session
     * @return JsonResponse
     */
    public function selectShipping(Request $request, CheckoutSession $session): JsonResponse
    {
        $request->validate([
            'shipping_method_id' => 'required|exists:shipping_methods,id',
        ]);

        // Verify session belongs to user
        if ($session->user_id && $session->user_id !== auth()->id()) {
            return response()->json([
                'error' => 'Unauthorized access to checkout session',
            ], 403);
        }

        try {
            $updatedSession = $this->checkoutService->selectShippingMethod(
                $session,
                $request->shipping_method_id
            );

            // Reserve inventory when shipping method is selected
            $this->checkoutService->reserveInventory($updatedSession);

            return response()->json([
                'data' => $updatedSession->load('shippingMethod'),
                'message' => 'Shipping method selected successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to select shipping method',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get checkout summary
     *
     * GET /api/v1/checkout/{session}/summary
     *
     * @param CheckoutSession $session
     * @return JsonResponse
     */
    public function summary(CheckoutSession $session): JsonResponse
    {
        // Verify session belongs to user
        if ($session->user_id && $session->user_id !== auth()->id()) {
            return response()->json([
                'error' => 'Unauthorized access to checkout session',
            ], 403);
        }

        $totals = $this->checkoutService->calculateTotals($session);

        return response()->json([
            'data' => [
                'session' => $session->load(['shoppingCart.cartItems.product', 'shippingMethod']),
                'totals' => $totals,
                'can_proceed_to_payment' => $session->can_proceed_to_payment,
                'time_remaining' => $session->time_remaining,
            ],
        ]);
    }

    /**
     * Cancel checkout session
     *
     * DELETE /api/v1/checkout/{session}
     *
     * @param CheckoutSession $session
     * @return JsonResponse
     */
    public function cancel(CheckoutSession $session): JsonResponse
    {
        // Verify session belongs to user
        if ($session->user_id && $session->user_id !== auth()->id()) {
            return response()->json([
                'error' => 'Unauthorized access to checkout session',
            ], 403);
        }

        try {
            // Release inventory reservations
            $this->checkoutService->releaseInventory($session);

            // Update session status
            $session->update(['status' => 'expired']);

            return response()->json([
                'message' => 'Checkout session cancelled successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to cancel checkout session',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
