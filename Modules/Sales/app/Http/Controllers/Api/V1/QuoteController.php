<?php

namespace Modules\Sales\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\QuoteItem;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Modules\Ecommerce\Models\ShoppingCart;
use Modules\Product\Models\Product;

class QuoteController extends Controller
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
     * Create a quote from a shopping cart
     * POST /api/v1/quotes/from-cart
     */
    public function createFromCart(Request $request): JsonResponse
    {
        $request->validate([
            'shopping_cart_id' => 'required|exists:shopping_carts,id',
            'contact_id' => 'required|exists:contacts,id',
            'valid_until' => 'nullable|date|after:today',
            'notes' => 'nullable|string|max:2000',
            'terms_and_conditions' => 'nullable|string|max:5000',
        ]);

        $cart = ShoppingCart::with('cartItems.product')->findOrFail($request->input('shopping_cart_id'));

        if ($cart->cartItems->isEmpty()) {
            return response()->json([
                'error' => 'Cannot create quote from empty cart'
            ], 400);
        }

        return DB::transaction(function () use ($request, $cart) {
            // Create quote
            $quote = Quote::create([
                'contact_id' => $request->input('contact_id'),
                'shopping_cart_id' => $cart->id,
                'quote_number' => Quote::generateQuoteNumber(),
                'status' => 'draft',
                'quote_date' => now(),
                'valid_until' => $request->input('valid_until') ?? now()->addDays(30),
                'currency' => $cart->currency ?? 'MXN',
                'notes' => $request->input('notes'),
                'terms_and_conditions' => $request->input('terms_and_conditions'),
                'shipping_address' => $request->input('shipping_address'),
                'billing_address' => $request->input('billing_address'),
            ]);

            // Create quote items from cart items
            foreach ($cart->cartItems as $cartItem) {
                $product = $cartItem->product;

                QuoteItem::create([
                    'quote_id' => $quote->id,
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->unit_price,
                    'quoted_price' => $cartItem->unit_price, // Initially same as unit price
                    'discount_percentage' => 0,
                    'tax_rate' => 16, // Default IVA México
                    'product_name' => $product?->name,
                    'product_sku' => $product?->sku,
                ]);
            }

            // Recalculate totals
            $quote->recalculateTotals();

            return response()->json([
                'data' => $this->transformQuote($quote->fresh(['items', 'contact'])),
                'message' => 'Quote created successfully from cart'
            ], 201);
        });
    }

    /**
     * Send quote to customer
     * POST /api/v1/quotes/{quote}/send
     */
    public function send(Quote $quote): JsonResponse
    {
        if (!$quote->canBeSent) {
            return response()->json([
                'error' => 'Quote cannot be sent. It must be in draft status with at least one item.'
            ], 400);
        }

        $quote->markAsSent();

        // TODO: Send email notification to customer
        // event(new QuoteSent($quote));

        return response()->json([
            'data' => $this->transformQuote($quote->fresh()),
            'message' => 'Quote sent successfully'
        ]);
    }

    /**
     * Mark quote as accepted
     * POST /api/v1/quotes/{quote}/accept
     */
    public function accept(Quote $quote): JsonResponse
    {
        if ($quote->status !== 'sent') {
            return response()->json([
                'error' => 'Only sent quotes can be accepted'
            ], 400);
        }

        $quote->markAsAccepted();

        return response()->json([
            'data' => $this->transformQuote($quote->fresh()),
            'message' => 'Quote accepted successfully'
        ]);
    }

    /**
     * Mark quote as rejected
     * POST /api/v1/quotes/{quote}/reject
     */
    public function reject(Request $request, Quote $quote): JsonResponse
    {
        if (!in_array($quote->status, ['sent', 'draft'])) {
            return response()->json([
                'error' => 'This quote cannot be rejected'
            ], 400);
        }

        $quote->markAsRejected();

        if ($request->has('reason')) {
            $quote->update([
                'internal_notes' => $quote->internal_notes . "\nRejection reason: " . $request->input('reason')
            ]);
        }

        return response()->json([
            'data' => $this->transformQuote($quote->fresh()),
            'message' => 'Quote rejected'
        ]);
    }

    /**
     * Convert quote to sales order
     * POST /api/v1/quotes/{quote}/convert
     */
    public function convert(Request $request, Quote $quote): JsonResponse
    {
        if (!$quote->canBeConverted) {
            return response()->json([
                'error' => 'Quote cannot be converted. It must be in accepted status and not already converted.'
            ], 400);
        }

        $request->validate([
            'shipping_address' => 'nullable|array',
            'billing_address' => 'nullable|array',
        ]);

        return DB::transaction(function () use ($request, $quote) {
            // Create sales order from quote
            $order = SalesOrder::create([
                'contact_id' => $quote->contact_id,
                'order_number' => 'SO-' . strtoupper(Str::random(8)),
                'status' => 'pending',
                'order_date' => now(),
                'subtotal' => $quote->subtotal_amount,
                'discount_total' => $quote->discount_amount,
                'tax_amount' => $quote->tax_amount,
                'total_amount' => $quote->total_amount,
                'notes' => $quote->notes,
                'shipping_address' => $request->input('shipping_address') ?? $quote->shipping_address,
                'billing_address' => $request->input('billing_address') ?? $quote->billing_address,
                'metadata' => [
                    'source' => 'quote',
                    'quote_id' => $quote->id,
                    'quote_number' => $quote->quote_number,
                ],
            ]);

            // Create order items from quote items
            foreach ($quote->items as $quoteItem) {
                SalesOrderItem::create([
                    'sales_order_id' => $order->id,
                    'product_id' => $quoteItem->product_id,
                    'quantity' => $quoteItem->quantity,
                    'unit_price' => $quoteItem->quoted_price,
                    'discount' => $quoteItem->discount_amount,
                    'total' => $quoteItem->total,
                    'metadata' => [
                        'quote_item_id' => $quoteItem->id,
                        'original_unit_price' => $quoteItem->unit_price,
                    ],
                ]);
            }

            // Update quote status
            $quote->update([
                'status' => 'converted',
                'sales_order_id' => $order->id,
                'converted_at' => now(),
            ]);

            return response()->json([
                'data' => [
                    'quote' => $this->transformQuote($quote->fresh()),
                    'salesOrder' => [
                        'type' => 'sales-orders',
                        'id' => (string) $order->id,
                        'attributes' => [
                            'orderNumber' => $order->order_number,
                            'status' => $order->status,
                            'totalAmount' => $order->total_amount,
                        ]
                    ]
                ],
                'message' => 'Quote converted to sales order successfully'
            ], 201);
        });
    }

    /**
     * Cancel a quote
     * POST /api/v1/quotes/{quote}/cancel
     */
    public function cancel(Quote $quote): JsonResponse
    {
        if (!$quote->cancel()) {
            return response()->json([
                'error' => 'This quote cannot be cancelled'
            ], 400);
        }

        return response()->json([
            'data' => $this->transformQuote($quote->fresh()),
            'message' => 'Quote cancelled successfully'
        ]);
    }

    /**
     * Duplicate a quote
     * POST /api/v1/quotes/{quote}/duplicate
     */
    public function duplicate(Quote $quote): JsonResponse
    {
        return DB::transaction(function () use ($quote) {
            $newQuote = $quote->replicate(['quote_number', 'status', 'sent_at', 'accepted_at', 'rejected_at', 'converted_at', 'sales_order_id']);
            $newQuote->quote_number = Quote::generateQuoteNumber();
            $newQuote->status = 'draft';
            $newQuote->quote_date = now();
            $newQuote->valid_until = now()->addDays(30);
            $newQuote->save();

            // Duplicate items
            foreach ($quote->items as $item) {
                $newItem = $item->replicate();
                $newItem->quote_id = $newQuote->id;
                $newItem->save();
            }

            return response()->json([
                'data' => $this->transformQuote($newQuote->fresh(['items', 'contact'])),
                'message' => 'Quote duplicated successfully'
            ], 201);
        });
    }

    /**
     * Get quotes expiring soon
     * GET /api/v1/quotes/expiring-soon
     */
    public function expiringSoon(Request $request): JsonResponse
    {
        $days = $request->input('days', 7);

        $quotes = Quote::with(['contact', 'items'])
            ->expiringSoon($days)
            ->orderBy('valid_until')
            ->get();

        return response()->json([
            'data' => $quotes->map(fn($q) => $this->transformQuote($q)),
            'meta' => [
                'count' => $quotes->count(),
                'days' => $days
            ]
        ]);
    }

    /**
     * Get quote statistics/summary
     * GET /api/v1/quotes/summary
     */
    public function summary(): JsonResponse
    {
        $stats = [
            'total' => Quote::count(),
            'draft' => Quote::draft()->count(),
            'sent' => Quote::sent()->count(),
            'accepted' => Quote::accepted()->count(),
            'converted' => Quote::byStatus('converted')->count(),
            'rejected' => Quote::byStatus('rejected')->count(),
            'expired' => Quote::byStatus('expired')->count(),
            'cancelled' => Quote::byStatus('cancelled')->count(),
            'totalValue' => Quote::active()->sum('total_amount'),
            'averageValue' => Quote::active()->avg('total_amount'),
            'conversionRate' => Quote::count() > 0
                ? round((Quote::byStatus('converted')->count() / Quote::count()) * 100, 2)
                : 0,
        ];

        return response()->json([
            'data' => $stats
        ]);
    }

    /**
     * Transform quote to JSON:API-like format
     */
    private function transformQuote(Quote $quote): array
    {
        return [
            'type' => 'quotes',
            'id' => (string) $quote->id,
            'attributes' => [
                'quoteNumber' => $quote->quote_number,
                'contactId' => $quote->contact_id,
                'shoppingCartId' => $quote->shopping_cart_id,
                'salesOrderId' => $quote->sales_order_id,
                'status' => $quote->status,
                'quoteDate' => $quote->quote_date?->toISOString(),
                'validUntil' => $quote->valid_until?->toISOString(),
                'estimatedEta' => $quote->estimated_eta,
                'subtotalAmount' => $quote->subtotal_amount,
                'discountAmount' => $quote->discount_amount,
                'taxAmount' => $quote->tax_amount,
                'totalAmount' => $quote->total_amount,
                'currency' => $quote->currency,
                'notes' => $quote->notes,
                'internalNotes' => $quote->internal_notes,
                'termsAndConditions' => $quote->terms_and_conditions,
                'shippingAddress' => $quote->shipping_address,
                'billingAddress' => $quote->billing_address,
                'sentAt' => $quote->sent_at?->toISOString(),
                'acceptedAt' => $quote->accepted_at?->toISOString(),
                'rejectedAt' => $quote->rejected_at?->toISOString(),
                'convertedAt' => $quote->converted_at?->toISOString(),
                'itemsCount' => $quote->itemsCount,
                'totalQuantity' => $quote->totalQuantity,
                'isExpired' => $quote->isExpired,
                'canBeSent' => $quote->canBeSent,
                'canBeConverted' => $quote->canBeConverted,
                'createdAt' => $quote->created_at?->toISOString(),
                'updatedAt' => $quote->updated_at?->toISOString(),
            ],
            'relationships' => [
                'contact' => $quote->relationLoaded('contact') && $quote->contact ? [
                    'data' => [
                        'type' => 'contacts',
                        'id' => (string) $quote->contact->id,
                    ]
                ] : null,
                'items' => $quote->relationLoaded('items') ? [
                    'data' => $quote->items->map(fn($item) => [
                        'type' => 'quote-items',
                        'id' => (string) $item->id,
                    ])->toArray()
                ] : null,
            ]
        ];
    }
}
