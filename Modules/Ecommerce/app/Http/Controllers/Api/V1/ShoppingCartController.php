<?php

namespace Modules\Ecommerce\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use Modules\Ecommerce\Models\ShoppingCart;
use Modules\Ecommerce\Models\Coupon;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;

class ShoppingCartController extends Controller
{
    use Actions\FetchMany;
    use Actions\FetchOne;
    use Actions\Store;
    use Actions\Update;
    use Actions\Destroy;
    use Actions\FetchRelated;
    use Actions\FetchRelationship;
    use Actions\UpdateRelationship;
    use Actions\AttachRelationship;
    use Actions\DetachRelationship;

    /**
     * Get or create shopping cart for current user/session
     * POST /api/v1/shopping-carts/get-or-create
     */
    public function getOrCreate(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $sessionId = $request->input('session_id') ?? ($request->hasSession() ? $request->session()->getId() : null);

        // Try to find existing active cart
        $cart = ShoppingCart::query()
            ->active()
            ->notExpired()
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->when(!$userId, fn($q) => $q->where('session_id', $sessionId))
            ->first();

        if (!$cart) {
            // Create new cart
            $cart = ShoppingCart::create([
                'user_id' => $userId,
                'session_id' => $userId ? null : $sessionId,
                'status' => 'active',
                'expires_at' => now()->addDays(7),
                'total_amount' => 0,
                'currency' => config('app.currency', 'MXN'),
            ]);
        }

        return response()->json([
            'data' => $this->transformCart($cart)
        ]);
    }

    /**
     * Get current cart for authenticated user or session
     * GET /api/v1/shopping-carts/current
     */
    public function current(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $sessionId = $request->input('session_id') ?? ($request->hasSession() ? $request->session()->getId() : null);

        $cart = ShoppingCart::query()
            ->with(['cartItems', 'cartItems.product'])
            ->active()
            ->notExpired()
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->when(!$userId, fn($q) => $q->where('session_id', $sessionId))
            ->first();

        if (!$cart) {
            return response()->json([
                'data' => null,
                'message' => 'No active cart found'
            ], 404);
        }

        return response()->json([
            'data' => $this->transformCart($cart)
        ]);
    }

    /**
     * Merge guest cart into user cart after login
     * POST /api/v1/shopping-carts/merge
     */
    public function merge(Request $request): JsonResponse
    {
        $request->validate([
            'guest_session_id' => 'required|string'
        ]);

        $userId = Auth::id();
        if (!$userId) {
            return response()->json([
                'error' => 'Authentication required'
            ], 401);
        }

        $guestSessionId = $request->input('guest_session_id');

        return DB::transaction(function () use ($userId, $guestSessionId) {
            // Find guest cart
            $guestCart = ShoppingCart::query()
                ->where('session_id', $guestSessionId)
                ->whereNull('user_id')
                ->active()
                ->first();

            // Find or create user cart
            $userCart = ShoppingCart::query()
                ->where('user_id', $userId)
                ->active()
                ->notExpired()
                ->first();

            if (!$userCart) {
                $userCart = ShoppingCart::create([
                    'user_id' => $userId,
                    'status' => 'active',
                    'expires_at' => now()->addDays(7),
                    'total_amount' => 0,
                    'currency' => config('app.currency', 'MXN'),
                ]);
            }

            if ($guestCart) {
                // Move items from guest cart to user cart
                foreach ($guestCart->cartItems as $item) {
                    $existingItem = $userCart->cartItems()
                        ->where('product_id', $item->product_id)
                        ->first();

                    if ($existingItem) {
                        // Increase quantity
                        $existingItem->increment('quantity', $item->quantity);
                        $existingItem->total = $existingItem->quantity * $existingItem->unit_price;
                        $existingItem->save();
                    } else {
                        // Move item to user cart
                        $item->update(['shopping_cart_id' => $userCart->id]);
                    }
                }

                // Mark guest cart as converted
                $guestCart->update(['status' => 'converted']);
            }

            // Recalculate totals
            $userCart->refresh();
            $userCart->total_amount = $userCart->subtotalAmount;
            $userCart->save();

            return response()->json([
                'data' => $this->transformCart($userCart),
                'message' => 'Carts merged successfully'
            ]);
        });
    }

    /**
     * Clear all items from cart
     * DELETE /api/v1/shopping-carts/{id}/clear
     */
    public function clear(ShoppingCart $shoppingCart): JsonResponse
    {
        $this->authorizeCartAccess($shoppingCart);

        $shoppingCart->cartItems()->delete();

        $shoppingCart->update([
            'total_amount' => 0,
            'discount_amount' => 0,
            'coupon_code' => null,
        ]);

        return response()->json([
            'data' => $this->transformCart($shoppingCart->refresh()),
            'message' => 'Cart cleared successfully'
        ]);
    }

    /**
     * Apply coupon to cart
     * POST /api/v1/shopping-carts/{id}/apply-coupon
     */
    public function applyCoupon(Request $request, ShoppingCart $shoppingCart): JsonResponse
    {
        $this->authorizeCartAccess($shoppingCart);

        $request->validate([
            'coupon_code' => 'required|string'
        ]);

        $couponCode = $request->input('coupon_code');

        // Find and validate coupon
        $coupon = Coupon::where('code', $couponCode)
            ->where('is_active', true)
            ->first();

        if (!$coupon) {
            return response()->json([
                'valid' => false,
                'error' => 'Invalid coupon code'
            ], 400);
        }

        // Check validity dates
        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            return response()->json([
                'valid' => false,
                'error' => 'Coupon is not yet active'
            ], 400);
        }

        if ($coupon->expires_at && $coupon->expires_at->isPast()) {
            return response()->json([
                'valid' => false,
                'error' => 'Coupon has expired'
            ], 400);
        }

        // Check usage limit
        if ($coupon->max_uses && $coupon->used_count >= $coupon->max_uses) {
            return response()->json([
                'valid' => false,
                'error' => 'Coupon usage limit reached'
            ], 400);
        }

        // Check minimum amount
        $subtotal = $shoppingCart->subtotalAmount;
        if ($coupon->min_amount && $subtotal < $coupon->min_amount) {
            return response()->json([
                'valid' => false,
                'error' => "Minimum order amount is {$coupon->min_amount}"
            ], 400);
        }

        // Calculate discount
        $discountAmount = $this->calculateDiscount($coupon, $subtotal);

        // Apply coupon
        $shoppingCart->update([
            'coupon_code' => $couponCode,
            'discount_amount' => $discountAmount,
        ]);

        // Increment usage count
        $coupon->increment('used_count');

        return response()->json([
            'valid' => true,
            'discount_amount' => $discountAmount,
            'new_total' => $shoppingCart->refresh()->finalTotal,
            'message' => 'Coupon applied successfully'
        ]);
    }

    /**
     * Remove coupon from cart
     * POST /api/v1/shopping-carts/{id}/remove-coupon
     */
    public function removeCoupon(ShoppingCart $shoppingCart): JsonResponse
    {
        $this->authorizeCartAccess($shoppingCart);

        $couponCode = $shoppingCart->coupon_code;

        if ($couponCode) {
            // Decrement usage count
            Coupon::where('code', $couponCode)->decrement('used_count');
        }

        $shoppingCart->update([
            'coupon_code' => null,
            'discount_amount' => 0,
        ]);

        return response()->json([
            'data' => $this->transformCart($shoppingCart->refresh()),
            'message' => 'Coupon removed successfully'
        ]);
    }

    /**
     * Convert cart to sales order (checkout)
     * POST /api/v1/shopping-carts/{id}/checkout
     */
    public function checkout(Request $request, ShoppingCart $shoppingCart): JsonResponse
    {
        $this->authorizeCartAccess($shoppingCart);

        $request->validate([
            'contact_id' => 'required|exists:contacts,id',
            'billing_address' => 'required|array',
            'shipping_address' => 'required|array',
        ]);

        if ($shoppingCart->isEmpty()) {
            return response()->json([
                'error' => 'Cannot checkout empty cart'
            ], 400);
        }

        if (!$shoppingCart->isActive()) {
            return response()->json([
                'error' => 'Cart is not active or has expired'
            ], 400);
        }

        return DB::transaction(function () use ($request, $shoppingCart) {
            // Create sales order
            $order = SalesOrder::create([
                'order_number' => 'SO-' . strtoupper(Str::random(8)),
                'contact_id' => $request->input('contact_id'),
                'status' => 'pending',
                'order_date' => now(),
                'subtotal' => $shoppingCart->subtotalAmount,
                'discount_total' => $shoppingCart->discount_amount ?? 0,
                'tax_amount' => $shoppingCart->tax_amount ?? 0,
                'total_amount' => $shoppingCart->finalTotal,
                'billing_address' => $request->input('billing_address'),
                'shipping_address' => $request->input('shipping_address'),
                'notes' => $shoppingCart->notes,
                'metadata' => [
                    'currency' => $shoppingCart->currency,
                    'shipping_amount' => $shoppingCart->shipping_amount ?? 0,
                ],
            ]);

            // Create order items
            foreach ($shoppingCart->cartItems as $cartItem) {
                SalesOrderItem::create([
                    'sales_order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->unit_price,
                    'discount' => $cartItem->discount_amount ?? 0,
                    'total' => $cartItem->total,
                ]);
            }

            // Mark cart as converted
            $shoppingCart->update(['status' => 'converted']);

            return response()->json([
                'data' => [
                    'type' => 'sales-orders',
                    'id' => (string) $order->id,
                    'attributes' => [
                        'orderNumber' => $order->order_number,
                        'status' => $order->status,
                        'totalAmount' => $order->total_amount,
                    ]
                ],
                'message' => 'Order created successfully'
            ], 201);
        });
    }

    /**
     * Calculate discount amount based on coupon type
     */
    private function calculateDiscount(Coupon $coupon, float $subtotal): float
    {
        $discount = match ($coupon->type) {
            'percentage' => $subtotal * ($coupon->value / 100),
            'fixed_amount' => $coupon->value,
            'free_shipping' => 0, // Handled separately
            default => 0,
        };

        // Apply max discount cap
        if ($coupon->max_amount && $discount > $coupon->max_amount) {
            $discount = $coupon->max_amount;
        }

        return round($discount, 2);
    }

    /**
     * Check if current user can access the shopping cart
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    private function authorizeCartAccess(ShoppingCart $shoppingCart): void
    {
        $user = Auth::user();

        // Admin/tech/god can access any cart
        if ($user && $user->hasAnyRole(['god', 'admin', 'tech'])) {
            return;
        }

        // Check ownership
        if ($shoppingCart->user_id) {
            // User cart - must belong to current user
            if (!$user || $shoppingCart->user_id !== $user->id) {
                abort(403, 'You do not have access to this cart');
            }
        } else {
            // Session cart - must match current session
            $sessionId = request()->input('session_id') ?? (request()->hasSession() ? request()->session()->getId() : null);
            if ($shoppingCart->session_id !== $sessionId) {
                abort(403, 'You do not have access to this cart');
            }
        }
    }

    /**
     * Transform cart to JSON:API format
     */
    private function transformCart(ShoppingCart $cart): array
    {
        return [
            'type' => 'shopping-carts',
            'id' => (string) $cart->id,
            'attributes' => [
                'sessionId' => $cart->session_id,
                'userId' => $cart->user_id,
                'status' => $cart->status,
                'expiresAt' => $cart->expires_at?->toISOString(),
                'totalAmount' => $cart->total_amount,
                'currency' => $cart->currency,
                'couponCode' => $cart->coupon_code,
                'discountAmount' => $cart->discount_amount,
                'taxAmount' => $cart->tax_amount,
                'shippingAmount' => $cart->shipping_amount,
                'notes' => $cart->notes,
                'itemsCount' => $cart->itemsCount,
                'subtotalAmount' => $cart->subtotalAmount,
                'finalTotal' => $cart->finalTotal,
                'isExpired' => $cart->isExpired,
                'canApplyCoupon' => $cart->canApplyCoupon,
                'createdAt' => $cart->created_at?->toISOString(),
                'updatedAt' => $cart->updated_at?->toISOString(),
            ]
        ];
    }
}
