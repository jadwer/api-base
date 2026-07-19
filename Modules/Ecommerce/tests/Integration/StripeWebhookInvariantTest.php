<?php

namespace Modules\Ecommerce\Tests\Integration;

use Carbon\Carbon;
use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Contacts\Models\Contact;
use Modules\Product\Models\Product;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\Stock;
use Modules\Inventory\Models\Warehouse;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Ecommerce\Models\ShoppingCart;
use Modules\Ecommerce\Models\CartItem;
use Modules\Ecommerce\Tests\Support\InteractsWithStripeWebhook;
use Modules\Finance\Models\ARInvoice;
use Modules\Finance\Models\Payment;
use Modules\Finance\Models\PaymentApplication;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Services\OrderStatusService;
use Modules\SatCatalogs\Models\SatFormaPago;
use Modules\Billing\Models\PaymentTransaction;
use Modules\Billing\Models\CFDIInvoice;

/**
 * Tests de INVARIANTE del webhook de Stripe (Fase 2.7 +
 * DESIGN_ECOMMERCE_PAGO_STOCK).
 *
 * Flujo real de punta a punta:
 *   1. La orden nace del checkout real (POST /shopping-carts/{id}/checkout
 *      con payment_intent_id).
 *   2. El PaymentTransaction nace del endpoint real POST /v1/stripe/payment-intents
 *      y los webhooks entran por POST /api/webhooks/stripe con firma HMAC
 *      genuina (maquinaria en el trait InteractsWithStripeWebhook; solo se
 *      mockea la llamada de red createPaymentIntent).
 *
 * Invariantes del diseno pago -> orden -> AR:
 *   - payment_intent.succeeded marca la orden paid (payment_status, dimension
 *     SEPARADA de financial_status) via MarkOrderPaidOnPaymentCaptured.
 *   - Un pago cuyo monto NO cuadra con la orden NO la marca paid: deja
 *     metadata.payment_mismatch para tratamiento manual.
 *   - charge.refunded marca transaccion y orden refunded (sin cancelar la
 *     orden ni reponer stock: eso es decision humana).
 *   - Al entregar una orden pagada, la AR nace sola y el cobro Stripe se le
 *     aplica por ARInvoicePaymentRegistrationService (ARPayment con folio +
 *     PaymentApplication + factura paid balance 0).
 *
 * QUEUE_CONNECTION=sync (phpunit.xml): los listeners ShouldQueue corren inline,
 * asi que si el efecto no ocurre no es por falta de queue worker (hallazgo H1
 * historico queda descartado como causa en este entorno).
 */
class StripeWebhookInvariantTest extends TestCase
{
    use InteractsWithStripeWebhook;

    private Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->warehouse = Warehouse::factory()->create(['is_active' => true]);
        config(['services.stripe.webhook_secret' => self::WEBHOOK_SECRET]);

        // Periodo fiscal abierto: el GL posting (COGS, factura AR y cobro) lo
        // exige en el test de entrega (mismo andamiaje que CycleSaleInvariantTest).
        FiscalPeriod::firstOrCreate(
            ['year' => now()->year, 'month' => now()->month],
            [
                'name' => now()->format('Y-m'),
                'start_date' => now()->startOfMonth()->format('Y-m-d'),
                'end_date' => now()->endOfMonth()->format('Y-m-d'),
                'status' => 'open',
            ]
        );

        // Limpieza de movimientos sembrados con reference_type del ciclo de
        // venta, para que las busquedas por reference_id no colisionen con ids
        // aleatorios del seeder.
        InventoryMovement::whereIn('reference_type', ['sales_order', 'sales_cancel'])->delete();
    }

    /**
     * Checkout real: crea la orden desde un carrito con stock suficiente.
     * El Stock lleva unit_cost > 0 para que la salida de la entrega genere un
     * COGS con importe real (el posting GL rechaza importes 0).
     */
    private function checkoutOrder(User $user, string $paymentIntentId): SalesOrder
    {
        Contact::firstOrCreate(['email' => $user->email], [
            'contact_type' => 'person',
            'name' => $user->name,
            'is_customer' => true,
            'status' => 'active',
        ]);

        // Divisa MXN explicita: ProductFactory asigna Currency::first() y si
        // no es MXN el CartItemObserver convierte precios y rompe los montos.
        $mxn = \Modules\Ecommerce\Models\Currency::firstOrCreate(['code' => 'MXN'], [
            'name' => 'Peso Mexicano',
            'symbol' => '$',
            'exchange_rate' => 1.0,
            'is_active' => true,
        ]);
        $product = Product::factory()->create(['price' => 100, 'currency_id' => $mxn->id]);
        Stock::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'warehouse_location_id' => null,
            'quantity' => 1000,
            'reserved_quantity' => 0,
            'unit_cost' => 50,
            'status' => 'active',
        ]);

        $cart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'session_id' => null,
            'status' => 'active',
            'expires_at' => now()->addDays(7),
            'currency' => 'MXN',
            'coupon_code' => null,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'shipping_amount' => 0,
        ]);
        CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 100,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'subtotal' => 200,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total' => 200,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/checkout", [
                'billing_address' => ['street' => 'Calle Webhook 1'],
                'shipping_address' => ['street' => 'Calle Webhook 1'],
                'payment_intent_id' => $paymentIntentId,
            ]);
        $response->assertStatus(201);

        return SalesOrder::findOrFail((int) $response->json('data.id'));
    }

    public function test_webhook_succeeded_marks_payment_transaction_captured(): void
    {
        $user = $this->getCustomerUser();
        $order = $this->checkoutOrder($user, 'pi_inv_capture');
        $this->createPaymentIntentViaEndpoint($user, $order, 'pi_inv_capture');

        $response = $this->postStripeWebhook(
            $this->succeededEvent('evt_inv_capture_1', 'pi_inv_capture', 20000)
        );
        $response->assertStatus(200);

        // Invariante en base: la transaccion quedo capturada y ligada a la orden
        $transaction = PaymentTransaction::where('payment_intent_id', 'pi_inv_capture')->firstOrFail();
        $this->assertSame('captured', $transaction->status);
        $this->assertNotNull($transaction->captured_at);
        $this->assertSame($order->id, $transaction->sales_order_id);
    }

    /**
     * DESIGN_ECOMMERCE_PAGO_STOCK (H-C): el eslabon webhook -> orden ya existe
     * (MarkOrderPaidOnPaymentCaptured). El pago marca payment_status='paid' +
     * paid_at + metadata; financial_status NO se toca porque es la dimension de
     * FACTURACION (el diseno las separa a proposito: la AR nace en la entrega).
     * El reintento del mismo webhook no re-marca (paid_at estable).
     */
    public function test_webhook_succeeded_marks_order_as_paid(): void
    {
        $user = $this->getCustomerUser();
        $order = $this->checkoutOrder($user, 'pi_inv_paid');
        $this->createPaymentIntentViaEndpoint($user, $order, 'pi_inv_paid');

        // Estado post-checkout: sin factura y sin pago reflejado
        $this->assertSame('not_invoiced', $order->financial_status);
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertNull($order->paid_at);

        $event = $this->succeededEvent('evt_inv_paid_1', 'pi_inv_paid', 20000);
        $this->postStripeWebhook($event)->assertStatus(200);

        $order->refresh();

        // Invariante: el cobro capturado marca la orden pagada con su rastro
        $this->assertSame(
            'paid',
            $order->payment_status,
            'La orden sigue payment_status=' . $order->payment_status
                . ' despues de capturar su pago Stripe: el webhook no propaga el cobro a sales_orders'
        );
        $this->assertNotNull($order->paid_at, 'paid_at debe quedar escrito al marcar la orden pagada');
        $this->assertSame('pi_inv_paid', $order->metadata['payment_intent_id'] ?? null);
        $this->assertArrayHasKey('payment_transaction_id', $order->metadata ?? []);

        // Dimension SEPARADA: pagar NO factura. financial_status es de
        // facturacion y sigue not_invoiced hasta que la AR nazca en la entrega.
        $this->assertSame(
            'not_invoiced',
            $order->financial_status,
            'payment_status y financial_status son dimensiones separadas: pagar no debe tocar la de facturacion'
        );

        // Reintento de Stripe (mismo evento) con el reloj corrido: si el
        // listener re-marcara, paid_at cambiaria. Debe quedar identico.
        $paidAtFirst = $order->paid_at->toDateTimeString();
        Carbon::setTestNow(now()->addMinutes(5));
        $this->postStripeWebhook($event)->assertStatus(200);

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame(
            $paidAtFirst,
            $order->paid_at->toDateTimeString(),
            'El reintento del mismo webhook no debe re-marcar la orden (paid_at debe ser estable)'
        );
    }

    /**
     * Conciliacion de monto: un pago capturado cuyo monto NO cuadra con el
     * total de la orden (tolerancia 1 centavo) NO la marca pagada. Queda
     * metadata.payment_mismatch con el detalle para tratamiento manual (pagos
     * parciales no existen en este flujo; si aparece uno, es anomalia).
     */
    public function test_webhook_succeeded_with_mismatched_amount_does_not_mark_order_paid(): void
    {
        $user = $this->getCustomerUser();
        $order = $this->checkoutOrder($user, 'pi_inv_mismatch');
        $this->assertEquals(200.0, (float) $order->total_amount);

        // El cliente creo el intent por 50.00 (5000 centavos) contra una orden
        // de 200.00: la transaccion nace con el monto equivocado.
        $this->createPaymentIntentViaEndpoint($user, $order, 'pi_inv_mismatch', amountCents: 5000);

        $this->postStripeWebhook(
            $this->succeededEvent('evt_inv_mismatch_1', 'pi_inv_mismatch', 5000)
        )->assertStatus(200);

        // La transaccion SI queda capturada (el dinero entro)...
        $transaction = PaymentTransaction::where('payment_intent_id', 'pi_inv_mismatch')->firstOrFail();
        $this->assertSame('captured', $transaction->status);
        $this->assertEquals(50.0, (float) $transaction->amount);

        // ...pero la orden NO se marca pagada y queda el rastro del mismatch
        $order->refresh();
        $this->assertSame(
            'unpaid',
            $order->payment_status,
            'Un pago que no cuadra con el total de la orden NO debe marcarla paid'
        );
        $this->assertNull($order->paid_at);

        $mismatch = $order->metadata['payment_mismatch'] ?? null;
        $this->assertNotNull($mismatch, 'Debe quedar metadata.payment_mismatch para tratamiento manual');
        $this->assertEquals(50.0, (float) $mismatch['transaction_amount']);
        $this->assertEquals(200.0, (float) $mismatch['order_total']);
    }

    /**
     * charge.refunded: StripeService resuelve la transaccion por
     * transaction_id == charge.id (el charge id que dejo el succeeded), la
     * marca refunded y dispara PaymentRefunded; el listener espejo de Sales
     * marca la orden payment_status='refunded'. NO cancela la orden ni repone
     * stock (la reversa operativa es decision humana).
     */
    public function test_charge_refunded_marks_transaction_and_order_refunded(): void
    {
        $user = $this->getCustomerUser();
        $order = $this->checkoutOrder($user, 'pi_inv_refund');
        $this->createPaymentIntentViaEndpoint($user, $order, 'pi_inv_refund');

        $this->postStripeWebhook(
            $this->succeededEvent('evt_inv_refund_1', 'pi_inv_refund', 20000)
        )->assertStatus(200);

        $transaction = PaymentTransaction::where('payment_intent_id', 'pi_inv_refund')->firstOrFail();
        $this->assertSame('captured', $transaction->status);
        // El succeeded dejo el charge id en transaction_id: es la llave con la
        // que handleChargeRefunded resolvera el refund.
        $this->assertSame('ch_inv_refund', $transaction->transaction_id);

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);

        $this->postStripeWebhook(
            $this->chargeRefundedEvent('evt_inv_refund_2', 'ch_inv_refund', 20000)
        )->assertStatus(200);

        // Transaccion refunded con su rastro
        $transaction->refresh();
        $this->assertSame('refunded', $transaction->status);
        $this->assertNotNull($transaction->refunded_at);

        // Orden refunded (deja de comprometer stock), pero NO cancelada
        $order->refresh();
        $this->assertSame(
            'refunded',
            $order->payment_status,
            'charge.refunded debe reflejarse en la orden como payment_status=refunded'
        );
        $this->assertNotSame('cancelled', $order->status, 'El refund NO cancela la orden en automatico');
        $this->assertArrayHasKey('refunded_at', $order->metadata ?? []);
        $this->assertArrayHasKey('refund_transaction_id', $order->metadata ?? []);
    }

    /**
     * Cierre del ciclo pago -> entrega -> AR: al entregar una orden pagada por
     * Stripe, CreateARInvoiceForSalesOrder crea la AR y le aplica el cobro via
     * ARInvoicePaymentRegistrationService (mismo camino auditado del dashboard):
     * Payment con folio + reference = payment_intent_id + PaymentApplication +
     * asiento DR banco / CR clientes; la factura queda paid balance 0.
     */
    public function test_delivering_paid_order_creates_ar_invoice_with_stripe_payment_applied(): void
    {
        // El registro del cobro hace SatFormaPago::findOrFail('04') (forma de
        // pago SAT default para Stripe): garantizarla sembrada en este env.
        SatFormaPago::firstOrCreate(['clave' => '04'], ['descripcion' => 'Tarjeta de crédito']);

        $user = $this->getCustomerUser();
        $order = $this->checkoutOrder($user, 'pi_inv_ar');
        $this->createPaymentIntentViaEndpoint($user, $order, 'pi_inv_ar');

        $this->postStripeWebhook(
            $this->succeededEvent('evt_inv_ar_1', 'pi_inv_ar', 20000)
        )->assertStatus(200);

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);

        // Camino real de entrega: la maquina de estados, sin saltos.
        $service = app(OrderStatusService::class);
        foreach (['confirmed', 'processing', 'shipped', 'delivered'] as $status) {
            $order = $service->updateStatus($order, $status);
        }
        $order = $order->fresh();

        // La AR nacio sola en la entrega y quedo ligada a la orden
        $invoice = ARInvoice::where('sales_order_id', $order->id)->where('is_active', true)->first();
        $this->assertNotNull($invoice, 'La entrega de una orden pagada debe crear la ARInvoice sola');
        $this->assertSame($invoice->id, $order->ar_invoice_id);
        $this->assertSame('invoiced', $order->invoicing_status);

        // El cobro Stripe quedo aplicado: factura liquidada, balance 0
        $this->assertSame(
            'paid',
            $invoice->status,
            'La AR de una orden pagada por Stripe debe nacer con el cobro aplicado (status paid). '
                . 'invoicing_notes: ' . ($order->invoicing_notes ?? 'null')
        );
        $this->assertEquals(200.0, (float) $invoice->total_amount);
        $this->assertEquals(
            200.0,
            (float) $invoice->paid_amount,
            'paid_amount debe quedar igual al total (balance 0)'
        );

        // Rastro completo del cobro: Payment aplicado con reference =
        // payment_intent_id, su PaymentApplication y su asiento GL
        $payment = Payment::where('reference', 'pi_inv_ar')->first();
        $this->assertNotNull($payment, 'Debe existir el Payment del cobro Stripe con reference = payment_intent_id');
        $this->assertSame('applied', $payment->status);
        $this->assertEquals(200.0, (float) $payment->amount);
        $this->assertEquals(200.0, (float) $payment->applied_amount);
        $this->assertNotNull($payment->journal_entry_id, 'El cobro debe dejar su asiento GL (DR banco / CR clientes)');

        $application = PaymentApplication::where('payment_id', $payment->id)
            ->where('ar_invoice_id', $invoice->id)
            ->where('is_active', true)
            ->first();
        $this->assertNotNull($application, 'Debe existir la PaymentApplication que liga cobro y factura');
        $this->assertEquals(200.0, (float) $application->amount);
    }

    public function test_webhook_retry_with_same_event_does_not_duplicate_effects(): void
    {
        $user = $this->getCustomerUser();
        $order = $this->checkoutOrder($user, 'pi_inv_retry');
        $this->createPaymentIntentViaEndpoint($user, $order, 'pi_inv_retry');

        $event = $this->succeededEvent('evt_inv_retry_1', 'pi_inv_retry', 20000);

        $this->postStripeWebhook($event)->assertStatus(200);

        $transactionsAfterFirst = PaymentTransaction::where('payment_intent_id', 'pi_inv_retry')->count();
        $ordersAfterFirst = SalesOrder::count();
        $cfdisAfterFirst = CFDIInvoice::count();
        $capturedAtFirst = PaymentTransaction::where('payment_intent_id', 'pi_inv_retry')
            ->value('captured_at');

        // Reintento de Stripe: mismo event id, mismo payload
        $this->postStripeWebhook($event)->assertStatus(200);

        // Invariantes de idempotencia contra la base
        $this->assertSame(
            $transactionsAfterFirst,
            PaymentTransaction::where('payment_intent_id', 'pi_inv_retry')->count(),
            'El reintento no debe crear payment_transactions adicionales'
        );
        $this->assertSame(1, $transactionsAfterFirst);
        $this->assertSame($ordersAfterFirst, SalesOrder::count(), 'El reintento no debe crear ordenes');
        $this->assertSame($cfdisAfterFirst, CFDIInvoice::count(), 'El reintento no debe duplicar CFDIs');

        $transaction = PaymentTransaction::where('payment_intent_id', 'pi_inv_retry')->firstOrFail();
        $this->assertSame('captured', $transaction->status);
        $this->assertNotNull($capturedAtFirst);
    }

    public function test_webhook_for_other_payment_intent_does_not_touch_this_order(): void
    {
        $userA = $this->getCustomerUser();
        $orderA = $this->checkoutOrder($userA, 'pi_inv_alpha');
        $this->createPaymentIntentViaEndpoint($userA, $orderA, 'pi_inv_alpha');

        $userB = User::factory()->create(['email' => 'webhook-b@example.com']);
        $userB->assignRole('customer');
        $orderB = $this->checkoutOrder($userB, 'pi_inv_beta');
        $this->createPaymentIntentViaEndpoint($userB, $orderB, 'pi_inv_beta');

        // Solo se paga la orden B
        $this->postStripeWebhook(
            $this->succeededEvent('evt_inv_beta_1', 'pi_inv_beta', 20000)
        )->assertStatus(200);

        // B capturada, A intacta
        $this->assertSame(
            'captured',
            PaymentTransaction::where('payment_intent_id', 'pi_inv_beta')->value('status')
        );
        $transactionA = PaymentTransaction::where('payment_intent_id', 'pi_inv_alpha')->firstOrFail();
        $this->assertSame('pending', $transactionA->status);
        $this->assertNull($transactionA->captured_at);

        $orderA->refresh();
        $this->assertSame('pending', $orderA->status);
        $this->assertSame('unpaid', $orderA->payment_status);
    }
}
