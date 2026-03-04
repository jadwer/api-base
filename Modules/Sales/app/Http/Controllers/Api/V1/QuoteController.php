<?php

namespace Modules\Sales\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\QuoteItem;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Modules\Sales\Models\FolioSequence;
use Modules\Sales\Services\QuotePDFGenerator;
use Modules\Ecommerce\Models\ShoppingCart;
use Modules\Product\Models\Product;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseOrderItem;
use Modules\Inventory\Models\Warehouse;
use Modules\Sales\Mail\QuoteConvertedMail;
use Illuminate\Support\Facades\Mail;
use Modules\Contacts\Models\Contact;
use App\Services\CurrencyConversionService;

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
        if (Gate::denies('quotes.store')) {
            abort(403, 'No tiene permisos para crear cotizaciones');
        }

        $request->validate([
            'shopping_cart_id' => 'required|exists:shopping_carts,id',
            'contact_id' => 'required|exists:contacts,id',
            'valid_until' => 'nullable|date|after:today',
            'notes' => 'nullable|string|max:2000',
            'terms_and_conditions' => 'nullable|string|max:5000',
        ]);

        $cart = ShoppingCart::with('cartItems.product.brand')->findOrFail($request->input('shopping_cart_id'));

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

                // Auto-populate ETA from brand if it has a default lead time
                $notes = null;
                if ($product?->brand?->default_lead_time) {
                    $notes = 'ETA: ' . $product->brand->default_lead_time;
                }

                QuoteItem::create([
                    'quote_id' => $quote->id,
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->unit_price,
                    'quoted_price' => $cartItem->unit_price, // Initially same as unit price
                    'discount_percentage' => 0,
                    'tax_rate' => ($product?->iva ?? false) ? 16 : 0,
                    'product_name' => $product?->name,
                    'product_sku' => $product?->sku,
                    'notes' => $notes,
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
        if (Gate::denies('quotes.update')) {
            abort(403, 'No tiene permisos para enviar cotizaciones');
        }

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
    public function accept(Request $request, Quote $quote): JsonResponse
    {
        if (!$this->canCustomerManageQuote($request, $quote) && Gate::denies('quotes.update')) {
            abort(403, 'No tiene permisos para aceptar cotizaciones');
        }

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
        if (!$this->canCustomerManageQuote($request, $quote) && Gate::denies('quotes.update')) {
            abort(403, 'No tiene permisos para rechazar cotizaciones');
        }

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
        if (Gate::denies('quotes.update')) {
            abort(403, 'No tiene permisos para convertir cotizaciones');
        }

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
                'quote_id' => $quote->id,
                'order_number' => FolioSequence::getNextFolio('sales_order'),
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

            // Send email notification to customer
            $this->sendQuoteConvertedEmail($quote, $order);

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
        if (Gate::denies('quotes.update')) {
            abort(403, 'No tiene permisos para cancelar cotizaciones');
        }

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
        if (Gate::denies('quotes.store')) {
            abort(403, 'No tiene permisos para duplicar cotizaciones');
        }

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
        if (Gate::denies('quotes.index')) {
            abort(403, 'No tiene permisos para ver cotizaciones');
        }

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
        if (Gate::denies('quotes.index')) {
            abort(403, 'No tiene permisos para ver resumen de cotizaciones');
        }

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
     * Generate PDF for quote
     * GET /api/v1/quotes/{quote}/pdf
     */
    public function generatePdf(Quote $quote): JsonResponse
    {
        if (Gate::denies('quotes.show')) {
            abort(403, 'No tiene permisos para generar PDF de cotizaciones');
        }

        $generator = new QuotePDFGenerator();
        $path = $generator->generate($quote);

        return response()->json([
            'data' => [
                'path' => $path,
                'url' => asset('storage/' . $path),
            ],
            'message' => 'PDF generated successfully'
        ]);
    }

    /**
     * Download quote PDF
     * GET /api/v1/quotes/{quote}/pdf/download
     */
    public function downloadPdf(Quote $quote)
    {
        if (Gate::denies('quotes.show')) {
            abort(403, 'No tiene permisos para descargar PDF de cotizaciones');
        }

        $generator = new QuotePDFGenerator();
        return $generator->download($quote);
    }

    /**
     * Preview quote PDF (inline)
     * GET /api/v1/quotes/{quote}/pdf/preview
     */
    public function previewPdf(Quote $quote)
    {
        if (Gate::denies('quotes.show')) {
            abort(403, 'No tiene permisos para previsualizar cotizaciones');
        }

        $generator = new QuotePDFGenerator();
        return $generator->preview($quote);
    }

    /**
     * Stream quote PDF (real-time generation without storing)
     * GET /api/v1/quotes/{quote}/pdf/stream
     */
    public function streamPdf(Quote $quote)
    {
        if (Gate::denies('quotes.show')) {
            abort(403, 'No tiene permisos para ver cotizaciones');
        }

        $generator = new QuotePDFGenerator();
        return $generator->stream($quote);
    }

    /**
     * Generate a Purchase Order from the quote
     * POST /api/v1/quotes/{quote}/generate-purchase-order
     *
     * This creates a PO to request products from suppliers based on the quote items.
     */
    public function generatePurchaseOrder(Request $request, Quote $quote): JsonResponse
    {
        if (Gate::denies('quotes.update')) {
            abort(403, 'No tiene permisos para generar ordenes de compra');
        }

        // Validate quote can generate PO
        if (!$quote->canGeneratePurchaseOrder) {
            return response()->json([
                'error' => 'Cannot generate purchase order. Quote must be accepted or converted and not already have a PO.'
            ], 400);
        }

        $request->validate([
            'supplier_id' => 'required|exists:contacts,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'notes' => 'nullable|string|max:2000',
        ]);

        return DB::transaction(function () use ($request, $quote) {
            // Get warehouse if not provided (use first active warehouse)
            $warehouseId = $request->input('warehouse_id');
            if (!$warehouseId) {
                $warehouse = Warehouse::where('is_active', true)->first()
                    ?? Warehouse::first();
                $warehouseId = $warehouse?->id;
            }

            // Calculate total from quote items (using cost if available, otherwise unit_price)
            $total = 0;
            foreach ($quote->items as $item) {
                $product = $item->product;
                $unitCost = $product?->cost ?? $item->unit_price;
                $total += $item->quantity * $unitCost;
            }

            // Create purchase order
            $purchaseOrder = PurchaseOrder::create([
                'order_number' => PurchaseOrder::generateOrderNumber(),
                'contact_id' => $request->input('supplier_id'),
                'quote_id' => $quote->id,
                'warehouse_id' => $warehouseId,
                'order_date' => now(),
                'status' => 'pending',
                'total_amount' => $total,
                'notes' => $request->input('notes') ?? "Generado desde cotizacion {$quote->quote_number}",
                'metadata' => [
                    'source' => 'quote',
                    'quote_id' => $quote->id,
                    'quote_number' => $quote->quote_number,
                ],
            ]);

            // Create purchase order items from quote items
            foreach ($quote->items as $quoteItem) {
                $product = $quoteItem->product;
                $unitCost = $product?->cost ?? $quoteItem->unit_price;

                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_id' => $quoteItem->product_id,
                    'quantity' => $quoteItem->quantity,
                    'unit_price' => $unitCost,
                    'discount' => 0,
                    'received_quantity' => 0,
                    'metadata' => [
                        'quote_item_id' => $quoteItem->id,
                        'original_quoted_price' => $quoteItem->quoted_price,
                    ],
                ]);
            }

            // Link quote to purchase order
            $quote->update([
                'purchase_order_id' => $purchaseOrder->id,
            ]);

            return response()->json([
                'data' => [
                    'quote' => $this->transformQuote($quote->fresh()),
                    'purchaseOrder' => [
                        'type' => 'purchase-orders',
                        'id' => (string) $purchaseOrder->id,
                        'attributes' => [
                            'orderNumber' => $purchaseOrder->order_number,
                            'status' => $purchaseOrder->status,
                            'totalAmount' => $purchaseOrder->total_amount,
                            'supplierId' => $purchaseOrder->contact_id,
                            'warehouseId' => $purchaseOrder->warehouse_id,
                        ]
                    ]
                ],
                'message' => 'Purchase order generated successfully from quote'
            ], 201);
        });
    }

    /**
     * Send email notification when quote is converted to order
     */
    protected function sendQuoteConvertedEmail(Quote $quote, SalesOrder $order): void
    {
        $quote->load(['contact', 'items.product']);
        $order->load(['items.product']);

        // Send to customer
        if ($quote->contact?->email) {
            try {
                Mail::to($quote->contact->email)
                    ->queue(new QuoteConvertedMail($quote, $order, false));
            } catch (\Exception $e) {
                \Log::error('Failed to send quote converted email to customer', [
                    'quote_id' => $quote->id,
                    'email' => $quote->contact->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Send to admin/sales team (optional - configurable)
        $adminEmail = config('sales.notifications.quote_converted_admin_email');
        if ($adminEmail) {
            try {
                Mail::to($adminEmail)
                    ->queue(new QuoteConvertedMail($quote, $order, true));
            } catch (\Exception $e) {
                \Log::error('Failed to send quote converted email to admin', [
                    'quote_id' => $quote->id,
                    'email' => $adminEmail,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Request a quote as a customer (simplified flow)
     * POST /api/v1/quotes/request
     *
     * This endpoint is for customers to request quotes without needing
     * to access the dashboard or select a contact. The authenticated
     * user's contact record is used automatically.
     */
    public function requestQuote(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'error' => 'Authentication required'
            ], 401);
        }

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:2000',
            'shipping_address' => 'nullable|array',
        ]);

        // Find or create contact for the user
        $contact = Contact::where('email', $user->email)->first();

        if (!$contact) {
            // Create a new contact for this user
            $contact = Contact::create([
                'contact_type' => 'individual',
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? null,
                'status' => 'active',
                'is_customer' => true,
                'is_supplier' => false,
            ]);
        }

        // Validate products exist and are active
        $productIds = collect($request->input('items'))->pluck('product_id')->unique();
        $products = Product::whereIn('id', $productIds)
            ->where('is_active', true)
            ->with(['brand', 'currency'])
            ->get()
            ->keyBy('id');

        if ($products->count() !== $productIds->count()) {
            return response()->json([
                'error' => 'One or more products are not available'
            ], 400);
        }

        $currencyService = app(CurrencyConversionService::class);
        $baseCurrency = 'MXN';

        return DB::transaction(function () use ($request, $contact, $products, $currencyService, $baseCurrency) {
            // Create quote
            $quote = Quote::create([
                'quote_number' => Quote::generateQuoteNumber(),
                'contact_id' => $contact->id,
                'status' => 'draft',
                'quote_date' => now(),
                'valid_until' => now()->addDays(30),
                'currency' => $baseCurrency,
                'notes' => $request->input('notes'),
                'shipping_address' => $request->input('shipping_address'),
            ]);

            // Create quote items
            foreach ($request->input('items') as $item) {
                $product = $products->get($item['product_id']);
                $productPrice = $product->price ?? 0;

                // Convert price if product is in a different currency
                $productCurrencyCode = $product->currency?->code ?? $baseCurrency;
                if ($productCurrencyCode !== $baseCurrency && $productPrice > 0) {
                    $conversion = $currencyService->convert($productPrice, $productCurrencyCode, $baseCurrency);
                    $productPrice = $conversion->convertedAmount;
                }

                // Get ETA from brand if available
                $eta = null;
                if ($product->brand && $product->brand->default_lead_time) {
                    $eta = "Tiempo de entrega estimado: {$product->brand->default_lead_time} días";
                }

                QuoteItem::create([
                    'quote_id' => $quote->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $productPrice,
                    'quoted_price' => $productPrice,
                    'discount_percentage' => 0,
                    'tax_rate' => $product->iva ? 16 : 0,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'notes' => $eta,
                ]);
            }

            // Recalculate totals
            $quote->recalculateTotals();

            // Auto-send the quote (mark as sent)
            $quote->markAsSent();

            // Log the quote request
            activity('quote_request')
                ->performedOn($quote)
                ->causedBy($contact)
                ->withProperties([
                    'contact_email' => $contact->email,
                    'items_count' => count($request->input('items')),
                    'total_amount' => $quote->total_amount,
                ])
                ->log('Customer requested a quote');

            return response()->json([
                'success' => true,
                'message' => 'Cotización solicitada exitosamente. Recibirás una respuesta en tu correo electrónico.',
                'data' => [
                    'quote_number' => $quote->quote_number,
                    'total_amount' => $quote->total_amount,
                    'items_count' => $quote->items->count(),
                    'valid_until' => $quote->valid_until?->toDateString(),
                ]
            ], 201);
        });
    }

    /**
     * Check if the authenticated user is the customer who owns this quote.
     * Allows customers to accept/reject their own quotes without needing quotes.update permission.
     */
    private function canCustomerManageQuote(Request $request, Quote $quote): bool
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return false;
        }

        $quote->loadMissing('contact');
        return $quote->contact && $quote->contact->email === $user->email;
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
                'purchaseOrderId' => $quote->purchase_order_id,
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
                'canGeneratePurchaseOrder' => $quote->canGeneratePurchaseOrder,
                'hasPurchaseOrder' => $quote->hasPurchaseOrder,
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
