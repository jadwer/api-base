<?php

namespace Modules\Ecommerce\Services;

use Modules\Ecommerce\Models\ShoppingCart;
use Modules\Ecommerce\Models\CheckoutSession;
use Modules\Ecommerce\Models\ShippingMethod;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Modules\Finance\Models\ARInvoice;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CheckoutService
{
    private InventoryReservationService $inventoryReservationService;

    public function __construct(InventoryReservationService $inventoryReservationService)
    {
        $this->inventoryReservationService = $inventoryReservationService;
    }

    /**
     * Initiate checkout from shopping cart
     *
     * @param ShoppingCart $cart
     * @param array $data
     * @return CheckoutSession
     */
    public function initiateCheckout(ShoppingCart $cart, array $data): CheckoutSession
    {
        // Validate cart is not empty
        if ($cart->isEmpty()) {
            throw new \Exception('Cannot checkout with empty cart');
        }

        // Validate cart is active
        if (!$cart->isActive()) {
            throw new \Exception('Cart is not active or has expired');
        }

        // Check if there's already an active checkout session
        $existingSession = CheckoutSession::where('shopping_cart_id', $cart->id)
            ->active()
            ->first();

        if ($existingSession) {
            return $existingSession;
        }

        // Calculate totals
        $subtotal = $cart->subtotalAmount;
        $discount = $cart->discount_amount ?? 0;
        $tax = $this->calculateTax($subtotal - $discount);

        // Create checkout session
        $session = CheckoutSession::create([
            'shopping_cart_id' => $cart->id,
            'user_id' => $cart->user_id,
            'status' => 'initiated',
            'step' => 'address',
            'contact_email' => $data['email'] ?? $cart->user->email ?? null,
            'contact_phone' => $data['phone'] ?? null,
            'subtotal_amount' => $subtotal,
            'discount_amount' => $discount,
            'tax_amount' => $tax,
            'shipping_amount' => 0, // Will be calculated when shipping method is selected
            'total_amount' => $subtotal - $discount + $tax,
            'currency' => $cart->currency ?? 'MXN',
            'expires_at' => now()->addMinutes(30), // 30 minutes to complete checkout
            'metadata' => [
                'initiated_at' => now()->toIso8601String(),
                'user_agent' => request()->userAgent(),
            ],
        ]);

        return $session;
    }

    /**
     * Update shipping address
     *
     * @param CheckoutSession $session
     * @param array $shippingAddress
     * @param array|null $billingAddress
     * @return CheckoutSession
     */
    public function updateShippingAddress(
        CheckoutSession $session,
        array $shippingAddress,
        ?array $billingAddress = null
    ): CheckoutSession {
        // Validate session can be updated
        if ($session->getIsExpiredAttribute()) {
            throw new \Exception('Checkout session has expired');
        }

        if ($session->status !== 'initiated') {
            throw new \Exception('Cannot update address for session in current status');
        }

        // Use shipping address as billing if not provided
        $billingAddress = $billingAddress ?? $shippingAddress;

        $session->update([
            'shipping_address' => $shippingAddress,
            'billing_address' => $billingAddress,
            'step' => 'shipping',
        ]);

        return $session->fresh();
    }

    /**
     * Select shipping method and calculate shipping cost
     *
     * @param CheckoutSession $session
     * @param int $shippingMethodId
     * @return CheckoutSession
     */
    public function selectShippingMethod(CheckoutSession $session, int $shippingMethodId): CheckoutSession
    {
        // Validate session
        if ($session->getIsExpiredAttribute()) {
            throw new \Exception('Checkout session has expired');
        }

        if ($session->step !== 'shipping') {
            throw new \Exception('Must complete address step first');
        }

        // Get shipping method
        $shippingMethod = ShippingMethod::active()->findOrFail($shippingMethodId);

        // Calculate shipping cost
        $shippingCost = $this->calculateShippingCost($session, $shippingMethod);

        // Update session
        $session->update([
            'shipping_method_id' => $shippingMethod->id,
            'shipping_amount' => $shippingCost,
            'total_amount' => $session->subtotal_amount
                - $session->discount_amount
                + $session->tax_amount
                + $shippingCost,
            'step' => 'payment',
        ]);

        return $session->fresh();
    }

    /**
     * Calculate totals for checkout session
     *
     * @param CheckoutSession $session
     * @return array
     */
    public function calculateTotals(CheckoutSession $session): array
    {
        return [
            'subtotal' => $session->subtotal_amount,
            'discount' => $session->discount_amount,
            'tax' => $session->tax_amount,
            'shipping' => $session->shipping_amount,
            'total' => $session->total_amount,
            'currency' => $session->currency,
        ];
    }

    /**
     * Validate inventory availability
     *
     * @param CheckoutSession $session
     * @return bool
     */
    public function validateInventoryAvailability(CheckoutSession $session): bool
    {
        $cart = $session->shoppingCart;

        foreach ($cart->cartItems as $item) {
            // Get available stock
            $availableStock = $item->product->stocks()
                ->where('quantity_on_hand', '>=', $item->quantity)
                ->first();

            if (!$availableStock) {
                return false;
            }
        }

        return true;
    }

    /**
     * Reserve inventory for checkout session
     *
     * @param CheckoutSession $session
     * @return array
     */
    public function reserveInventory(CheckoutSession $session): array
    {
        // Validate inventory before reserving
        if (!$this->validateInventoryAvailability($session)) {
            throw new \Exception('Insufficient inventory for one or more items');
        }

        // Reserve items
        return $this->inventoryReservationService->reserveItems($session);
    }

    /**
     * Release inventory reservations
     *
     * @param CheckoutSession $session
     * @return void
     */
    public function releaseInventory(CheckoutSession $session): void
    {
        foreach ($session->inventoryReservations()->active()->get() as $reservation) {
            $this->inventoryReservationService->releaseReservation($reservation, 'Checkout cancelled or expired');
        }
    }

    /**
     * Complete checkout and create order
     *
     * @param CheckoutSession $session
     * @return SalesOrder
     */
    public function completeCheckout(CheckoutSession $session): SalesOrder
    {
        if (!$session->canBeCompleted()) {
            throw new \Exception('Checkout session cannot be completed in current state');
        }

        return DB::transaction(function () use ($session) {
            $cart = $session->shoppingCart;

            // Create sales order
            $order = SalesOrder::create([
                'customer_id' => $cart->user_id,
                'order_number' => $this->generateOrderNumber(),
                'order_date' => now(),
                'status' => 'confirmed',
                'payment_status' => 'paid',
                'order_source' => 'ecommerce',
                'checkout_session_id' => $session->id,
                'subtotal' => $session->subtotal_amount,
                'tax_amount' => $session->tax_amount,
                'shipping_amount' => $session->shipping_amount,
                'discount_amount' => $session->discount_amount,
                'total_amount' => $session->total_amount,
                'currency' => $session->currency,
                'notes' => 'Order created from e-commerce checkout',
                'shipping_address' => $session->shipping_address,
                'billing_address' => $session->billing_address,
                'metadata' => [
                    'checkout_session_id' => $session->id,
                    'payment_intent_id' => $session->payment_intent_id,
                ],
            ]);

            // Create order items
            foreach ($cart->cartItems as $cartItem) {
                SalesOrderItem::create([
                    'sales_order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->price,
                    'subtotal' => $cartItem->total,
                    'tax_amount' => 0, // Tax calculated at order level
                    'total' => $cartItem->total,
                    'notes' => $cartItem->notes,
                ]);
            }

            // Mark session as completed
            $session->markAsCompleted();

            // Mark cart as completed/converted
            $cart->update(['status' => 'completed']);

            // Fulfill inventory reservations
            $this->fulfillInventoryReservations($session);

            // Emit event for Finance integration (AR Invoice creation)
            event(new \Modules\Sales\Events\SalesOrderCompleted($order));

            return $order;
        });
    }

    /**
     * Fulfill inventory reservations after order creation
     *
     * @param CheckoutSession $session
     * @return void
     */
    private function fulfillInventoryReservations(CheckoutSession $session): void
    {
        foreach ($session->inventoryReservations()->active()->get() as $reservation) {
            $this->inventoryReservationService->fulfillReservation($reservation);
        }
    }

    /**
     * Calculate tax amount
     *
     * @param float $amount
     * @return float
     */
    private function calculateTax(float $amount): float
    {
        // Default tax rate: 16% (IVA in Mexico)
        $taxRate = config('ecommerce.tax_rate', 0.16);

        return round($amount * $taxRate, 2);
    }

    /**
     * Calculate shipping cost
     *
     * @param CheckoutSession $session
     * @param ShippingMethod $shippingMethod
     * @return float
     */
    private function calculateShippingCost(CheckoutSession $session, ShippingMethod $shippingMethod): float
    {
        $cart = $session->shoppingCart;

        // Calculate total weight
        $totalWeight = 0;
        foreach ($cart->cartItems as $item) {
            $productWeight = $item->product->weight ?? 0;
            $totalWeight += ($productWeight * $item->quantity);
        }

        return $shippingMethod->calculateShippingCost($totalWeight);
    }

    /**
     * Generate unique order number
     *
     * @return string
     */
    private function generateOrderNumber(): string
    {
        $prefix = 'ORD';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(md5(uniqid()), 0, 6));

        return "{$prefix}-{$date}-{$random}";
    }
}
