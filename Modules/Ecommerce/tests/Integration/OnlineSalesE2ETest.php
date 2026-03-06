<?php

namespace Modules\Ecommerce\Tests\Integration;

use Tests\TestCase;
use Illuminate\Support\Facades\Event;
use Modules\User\Models\User;
use Modules\Contacts\Models\Contact;
use Modules\Product\Models\Product;
use Modules\Ecommerce\Models\ShoppingCart;
use Modules\Ecommerce\Models\CartItem;
use Modules\Ecommerce\Models\CheckoutSession;
use Modules\Ecommerce\Services\CheckoutService;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Events\SalesOrderCompleted;
use Modules\Finance\Models\ARInvoice;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Models\FiscalPeriod;

/**
 * Online Sales E2E Integration Test
 *
 * Tests the complete online sales workflow:
 * Cart -> Checkout -> SalesOrder -> ARInvoice
 */
class OnlineSalesE2ETest extends TestCase
{
    /**
     * Test complete online sales flow from cart to SalesOrder
     */
    public function test_complete_online_sales_flow(): void
    {
        // ===== STEP 1: Create user =====
        $user = User::factory()->create([
            'name' => 'Juan Pérez E2E',
            'email' => 'juan.perez.e2e@example.com',
        ]);
        $user->assignRole('customer');

        // ===== STEP 2: Create cart with items using factories =====
        $cart = ShoppingCart::factory()->active()->create([
            'user_id' => $user->id,
            'currency' => 'MXN',
        ]);

        $product = Product::factory()->create(['is_active' => true, 'price' => 500.00]);

        CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 500.00,
            'total' => 1000.00,
        ]);

        $this->assertCount(1, $cart->cartItems);

        // ===== STEP 3: Create checkout session ready for completion =====
        $checkoutSession = CheckoutSession::factory()->paymentConfirmed()->create([
            'shopping_cart_id' => $cart->id,
            'user_id' => $user->id,
            'contact_email' => $user->email,
            'contact_phone' => '+52 55 1234 5678',
            'subtotal_amount' => 1000.00,
            'tax_amount' => 160.00,
            'total_amount' => 1160.00,
            'currency' => 'MXN',
        ]);

        $this->assertTrue($checkoutSession->canBeCompleted());

        // ===== STEP 4: Complete checkout =====
        Event::fake([SalesOrderCompleted::class]);

        $checkoutService = app(CheckoutService::class);
        $salesOrder = $checkoutService->completeCheckout($checkoutSession->fresh());

        // Verify SalesOrder was created
        $this->assertNotNull($salesOrder);
        $this->assertEquals('confirmed', $salesOrder->status);
        $this->assertEquals('ecommerce', $salesOrder->order_source);
        $this->assertNotNull($salesOrder->contact_id);

        // Verify Contact was created
        $contact = Contact::find($salesOrder->contact_id);
        $this->assertNotNull($contact);
        $this->assertEquals($user->email, $contact->email);
        $this->assertTrue($contact->is_customer);

        // Verify order items
        $this->assertCount(1, $salesOrder->items);

        // Verify event was dispatched
        Event::assertDispatched(SalesOrderCompleted::class);

        // Verify checkout session and cart are completed
        $checkoutSession->refresh();
        $cart->refresh();

        $this->assertEquals('completed', $checkoutSession->status);
        $this->assertEquals('completed', $cart->status);
    }

    /**
     * Test that Contact is reused for returning customers
     */
    public function test_contact_is_reused_for_returning_customer(): void
    {
        // Create existing customer contact
        $existingContact = Contact::factory()->create([
            'email' => 'returning.customer.e2e@example.com',
            'name' => 'Returning Customer',
            'is_customer' => true,
            'is_supplier' => false,
        ]);

        // Create user with same email
        $user = User::factory()->create([
            'name' => 'Returning Customer',
            'email' => 'returning.customer.e2e@example.com',
        ]);
        $user->assignRole('customer');

        // Setup cart and checkout using factories
        $cart = ShoppingCart::factory()->active()->create(['user_id' => $user->id]);
        $product = Product::factory()->create(['is_active' => true]);

        CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100.00,
            'total' => 100.00,
        ]);

        $checkoutSession = CheckoutSession::factory()->paymentConfirmed()->create([
            'shopping_cart_id' => $cart->id,
            'user_id' => $user->id,
            'contact_email' => $user->email,
        ]);

        Event::fake([SalesOrderCompleted::class]);

        $checkoutService = app(CheckoutService::class);
        $salesOrder = $checkoutService->completeCheckout($checkoutSession);

        // Verify existing Contact was reused
        $this->assertEquals($existingContact->id, $salesOrder->contact_id);

        // Verify no new Contact was created
        $contactCount = Contact::where('email', 'returning.customer.e2e@example.com')->count();
        $this->assertEquals(1, $contactCount);
    }

    /**
     * Test that expired checkout sessions cannot be completed
     */
    public function test_expired_checkout_cannot_be_completed(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        $cart = ShoppingCart::factory()->active()->create(['user_id' => $user->id]);
        $product = Product::factory()->create(['is_active' => true]);

        CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
        ]);

        // Create expired checkout session
        $checkoutSession = CheckoutSession::factory()->expired()->create([
            'shopping_cart_id' => $cart->id,
            'user_id' => $user->id,
            'status' => 'payment_confirmed', // Even with payment confirmed
            'payment_intent_id' => 'pi_expired_test',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Checkout session cannot be completed in current state');

        $checkoutService = app(CheckoutService::class);
        $checkoutService->completeCheckout($checkoutSession);
    }

    /**
     * Test cart migration from guest to logged-in user
     */
    public function test_cart_migration_preserves_items(): void
    {
        $product = Product::factory()->create(['is_active' => true]);

        // Create guest cart using factory
        $cart = ShoppingCart::factory()->active()->create([
            'session_id' => 'sess_guest_' . md5(uniqid()),
            'user_id' => null,
        ]);

        CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'total' => 600.00,
        ]);

        $originalItemCount = $cart->cartItems()->count();
        $originalTotal = $cart->cartItems()->sum('total');

        // Create user and migrate cart
        $user = User::factory()->create();

        $cart->update([
            'user_id' => $user->id,
            'session_id' => null,
        ]);

        $cart->refresh();

        // Verify migration
        $this->assertEquals($user->id, $cart->user_id);
        $this->assertNull($cart->session_id);
        $this->assertEquals($originalItemCount, $cart->cartItems()->count());
        $this->assertEquals($originalTotal, $cart->cartItems()->sum('total'));
    }

    /**
     * Test ARInvoice creation from SalesOrderCompleted event
     *
     * This test requires full accounting setup:
     * - GL Accounts (1104 Clientes, 4101 Ventas)
     * - AR Journal
     * - Open Fiscal Period for current date
     */
    public function test_ar_invoice_created_from_sales_order_event(): void
    {
        // ===== ACCOUNTING SETUP =====
        // Create or get required GL Accounts
        $customerAccount = Account::firstOrCreate(
            ['code' => '1104'],
            [
                'name' => 'Clientes',
                'account_type' => 'asset',
                'nature' => 'debit',
                'is_postable' => true,
                'status' => 'active',
                'level' => 3,
                'currency' => 'MXN',
                'metadata' => [],
            ]
        );
        $customerAccount->update(['is_postable' => true, 'status' => 'active']);

        $revenueAccount = Account::firstOrCreate(
            ['code' => '4101'],
            [
                'name' => 'Ingresos por Ventas',
                'account_type' => 'revenue',
                'nature' => 'credit',
                'is_postable' => true,
                'status' => 'active',
                'level' => 3,
                'currency' => 'MXN',
                'metadata' => [],
            ]
        );
        $revenueAccount->update(['is_postable' => true, 'status' => 'active']);

        // Create or get AR Journal
        Journal::firstOrCreate(
            ['code' => 'AR'],
            [
                'name' => 'Accounts Receivable Journal',
                'type' => 'sales',
                'status' => 'active',
                'prefix' => 'AR',
                'metadata' => [],
            ]
        );

        // Create open fiscal period for current month if not exists
        $now = \Carbon\Carbon::now();
        FiscalPeriod::firstOrCreate(
            ['year' => $now->year, 'month' => $now->month],
            [
                'name' => $now->format('Y-m'),
                'start_date' => $now->copy()->startOfMonth()->format('Y-m-d'),
                'end_date' => $now->copy()->endOfMonth()->format('Y-m-d'),
                'status' => 'open',
                'metadata' => [],
            ]
        );

        // ===== BUSINESS DATA SETUP =====
        $contact = Contact::factory()->create([
            'is_customer' => true,
            'is_supplier' => false,
            'status' => 'active',
            'credit_limit' => 100000, // High limit to avoid credit issues
            'credit_status' => 'active',
        ]);

        $product = Product::factory()->create(['is_active' => true]);

        // Create sales order directly
        $salesOrder = SalesOrder::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'delivered',
            'order_source' => 'ecommerce',
            'subtotal' => 1000.00,
            'tax_amount' => 160.00,
            'total_amount' => 1160.00,
            'ar_invoice_id' => null,
        ]);

        $salesOrder->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 500.00,
            'discount' => 0,
            'total' => 1000.00,
        ]);

        // ===== ACTION: Dispatch event =====
        event(new SalesOrderCompleted($salesOrder->fresh()));

        // ===== ASSERTIONS =====
        $salesOrder->refresh();
        $this->assertNotNull($salesOrder->ar_invoice_id, 'ARInvoice should be auto-created');

        $arInvoice = ARInvoice::find($salesOrder->ar_invoice_id);
        $this->assertNotNull($arInvoice);
        $this->assertEquals($contact->id, $arInvoice->contact_id);
        $this->assertEquals('invoiced', $salesOrder->invoicing_status);
        $this->assertEquals('posted', $arInvoice->status);
        $this->assertNotNull($arInvoice->journal_entry_id, 'GL posting should be created');
    }
}
