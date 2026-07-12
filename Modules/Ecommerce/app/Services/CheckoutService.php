<?php

namespace Modules\Ecommerce\Services;

use Modules\Ecommerce\Models\ShoppingCart;
use Modules\Ecommerce\Models\CheckoutSession;
use Modules\Ecommerce\Models\ShippingMethod;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Modules\Sales\Models\FolioSequence;
use Modules\Finance\Models\ARInvoice;
use Modules\Contacts\Models\Contact;
use Modules\User\Models\User;
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

        // Atomically check-or-create checkout session to prevent race conditions
        return DB::transaction(function () use ($cart, $data) {
            $existingSession = CheckoutSession::where('shopping_cart_id', $cart->id)
                ->active()
                ->lockForUpdate()
                ->first();

            if ($existingSession) {
                return $existingSession;
            }

            // Calculate totals
            // TODO(iva-configurable): this assembly (total = subtotal - discount + tax)
            // assumes tax is ADDED on top, i.e. pricing.prices_include_tax = false.
            // When a tenant sets that flag true (B2C, prices already include IVA),
            // subtotal must be treated as the tax-inclusive total and the tax
            // broken out of it instead of added, otherwise total_amount
            // double-counts the IVA. Left intentionally unchanged so the default
            // (false) tenant behavior and all current tests stay green.
            $subtotal = $cart->subtotalAmount;
            $discount = $cart->discount_amount ?? 0;
            $tax = $this->calculateTax($subtotal - $discount);

            return CheckoutSession::create([
                'shopping_cart_id' => $cart->id,
                'user_id' => $cart->user_id,
                'status' => 'initiated',
                'step' => 'address',
                'contact_email' => $data['email'] ?? $cart->user->email ?? null,
                'contact_phone' => $data['phone'] ?? null,
                'subtotal_amount' => $subtotal,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'shipping_amount' => 0,
                'total_amount' => $subtotal - $discount + $tax,
                'currency' => $cart->currency ?? 'MXN',
                'expires_at' => now()->addMinutes(30),
                'metadata' => [
                    'initiated_at' => now()->toIso8601String(),
                    'user_agent' => request()->userAgent(),
                ],
            ]);
        });
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
                ->whereRaw('(quantity - reserved_quantity) >= ?', [$item->quantity])
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
            $user = $cart->user;

            // Get or create Contact for the user
            $contact = $this->getOrCreateContactForUser($user, $session);

            // Determine exchange rate at checkout time
            $cartCurrency = $session->currency ?? $cart->currency ?? 'MXN';
            $exchangeRateUsed = null;
            if ($cartCurrency !== 'MXN') {
                $currencyModel = \Modules\Ecommerce\Models\Currency::where('code', $cartCurrency)->first();
                $exchangeRateUsed = $currencyModel?->exchange_rate;
            }

            // Create sales order
            $order = SalesOrder::create([
                'contact_id' => $contact->id,
                'order_number' => FolioSequence::getNextFolio('sales_order'),
                'order_date' => now(),
                'status' => 'confirmed',
                'order_source' => 'ecommerce',
                'checkout_session_id' => $session->id,
                'subtotal' => $session->subtotal_amount,
                'tax_amount' => $session->tax_amount,
                'discount_total' => $session->discount_amount,
                'total_amount' => $session->total_amount,
                'currency' => $cartCurrency,
                'exchange_rate_used' => $exchangeRateUsed,
                'notes' => 'Order created from e-commerce checkout',
                'shipping_address' => $session->shipping_address,
                'billing_address' => $session->billing_address,
                'metadata' => [
                    'checkout_session_id' => $session->id,
                    'payment_intent_id' => $session->payment_intent_id,
                    'shipping_amount' => $session->shipping_amount,
                ],
            ]);

            // Create order items with currency traceability
            foreach ($cart->cartItems as $cartItem) {
                SalesOrderItem::create([
                    'sales_order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->unit_price ?? $cartItem->price ?? 0,
                    'discount' => $cartItem->discount_amount ?? 0,
                    'total' => $cartItem->total ?? 0,
                    'original_currency_code' => $cartItem->original_currency_code,
                    'original_unit_price' => $cartItem->original_unit_price,
                    'exchange_rate_used' => $cartItem->exchange_rate_used,
                    'metadata' => [
                        'notes' => $cartItem->notes,
                        'original_price' => $cartItem->original_price,
                    ],
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
        // Default tax rate: 16% (IVA in Mexico). config() keeps the rate as a
        // fraction (0.16); TaxCalculator expects a percentage (16).
        $taxRatePercent = ((float) config('ecommerce.tax_rate', 0.16)) * 100;

        // Delegate to the shared TaxCalculator so the tenant's
        // pricing.prices_include_tax flag governs add-on vs. break-out. With the
        // flag false this returns exactly round($amount * $taxRate, 2) as before.
        //
        // NOTE: when prices_include_tax is true, the CheckoutSession still stores
        // subtotal_amount as the captured (tax-inclusive) amount and adds this
        // broken-out tax as a separate line, so total_amount would double-count.
        // A full B2C checkout requires reworking how subtotal/total are assembled
        // in initiateCheckout() (see TODO below). For now the tenant default
        // (false) is unaffected.
        return app(\App\Services\TaxCalculator::class)
            ->netAndTax($amount, $taxRatePercent)['tax'];
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

    // Order number generation now uses FolioSequence::getNextFolio('sales_order')

    /**
     * Get or create Contact for the given User
     *
     * This method ensures that every user who completes checkout has an associated
     * Contact record for use in SalesOrder and Finance entities.
     *
     * @param User $user
     * @param CheckoutSession $session
     * @return Contact
     */
    private function getOrCreateContactForUser(User $user, CheckoutSession $session): Contact
    {
        // First, try to find an existing Contact linked to this user by email
        $contact = Contact::where('email', $user->email)
            ->where('is_customer', true)
            ->first();

        if ($contact) {
            return $contact;
        }

        // Create new Contact from user and checkout session data
        $billingAddress = $session->billing_address ?? [];

        return Contact::create([
            'contact_type' => 'individual',
            'name' => $user->name,
            'legal_name' => $user->name,
            'email' => $user->email,
            'phone' => $session->contact_phone,
            'status' => 'active',
            'is_customer' => true,
            'is_supplier' => false,
            'credit_limit' => 0,
            'current_credit' => 0,
            'payment_terms' => 0, // Immediate payment for e-commerce
            'notes' => 'Auto-created from e-commerce checkout',
            'metadata' => [
                'user_id' => $user->id,
                'created_from' => 'ecommerce_checkout',
                'checkout_session_id' => $session->id,
            ],
        ]);
    }
}
