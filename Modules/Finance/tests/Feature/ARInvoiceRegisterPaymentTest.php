<?php

namespace Modules\Finance\Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\AppConfig\Models\AppSetting;
use Modules\Commissions\Models\Commission;
use Modules\Contacts\Models\Contact;
use Modules\Finance\Models\ARInvoice;
use Modules\Finance\Models\Payment;
use Modules\Finance\Models\PaymentApplication;
use Modules\Finance\Models\PaymentMethod;
use Modules\Sales\Models\SalesOrder;
use Modules\User\Models\User;
use Tests\TestCase;

/**
 * Fase B CxC - Registro de pago sobre factura AR.
 *
 * POST /api/v1/ar-invoices/{arInvoice}/register-payment
 *
 * Pasa por PaymentApplicationService::applyPayment para que el evento
 * ARInvoiceFullyPaid se dispare al liquidar (integracion con Commissions).
 */
class ARInvoiceRegisterPaymentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Periodo fiscal abierto para que el GL posting del pago funcione
        FiscalPeriod::firstOrCreate(
            ['year' => now()->year, 'month' => now()->month],
            [
                'name' => now()->format('Y-m'),
                'start_date' => now()->startOfMonth()->format('Y-m-d'),
                'end_date' => now()->endOfMonth()->format('Y-m-d'),
                'status' => 'open',
            ]
        );
    }

    private function makePostedInvoice(float $total = 1160.00, array $overrides = []): ARInvoice
    {
        $customer = Contact::factory()->customer()->create();

        return ARInvoice::factory()->create(array_merge([
            'contact_id' => $customer->id,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'currency' => 'MXN',
            'subtotal' => round($total / 1.16, 2),
            'tax_amount' => round($total - $total / 1.16, 2),
            'total_amount' => $total,
            'paid_amount' => 0,
            'status' => 'posted',
            'is_active' => true,
        ], $overrides));
    }

    private function registerPayment(ARInvoice $invoice, array $payload = [])
    {
        return $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson("/api/v1/ar-invoices/{$invoice->id}/register-payment", array_merge([
                'payment_date' => now()->format('Y-m-d'),
                'amount' => 100.00,
                'forma_pago' => '03',
            ], $payload));
    }

    public function test_admin_can_register_partial_payment(): void
    {
        $invoice = $this->makePostedInvoice(1000.00);

        $response = $this->registerPayment($invoice, [
            'amount' => 400.00,
            'forma_pago' => '03',
            'reference' => 'REF-BANCO-123',
            'comments' => 'Abono parcial via transferencia',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('invoice.id', $invoice->id)
            ->assertJsonPath('invoice.status', 'partial')
            ->assertJsonStructure(['message', 'payment' => ['id', 'payment_number'], 'invoice']);

        $this->assertEquals(1000.00, $response->json('invoice.total_amount'));
        $this->assertEquals(400.00, $response->json('invoice.paid_amount'));
        $this->assertEquals(600.00, $response->json('invoice.balance'));

        $payment = Payment::findOrFail($response->json('payment.id'));
        $this->assertEquals(400.00, $payment->amount);
        $this->assertEquals(400.00, $payment->applied_amount);
        $this->assertEquals('applied', $payment->status);
        $this->assertEquals('REF-BANCO-123', $payment->reference);
        $this->assertEquals('Abono parcial via transferencia', $payment->notes);
        $this->assertEquals('03', $payment->metadata['forma_pago'] ?? null);
        $this->assertEquals($invoice->contact_id, $payment->contact_id);
        $this->assertNotNull($payment->journal_entry_id, 'El pago debe tener GL posting');

        // Forma de pago SAT mapeada al catalogo PaymentMethod por code
        $this->assertEquals('03', PaymentMethod::find($payment->payment_method_id)?->code);

        $this->assertDatabaseHas('payment_applications', [
            'payment_id' => $payment->id,
            'ar_invoice_id' => $invoice->id,
            'amount' => 400.00,
            'is_active' => true,
        ]);
    }

    public function test_full_payment_marks_invoice_paid_and_earns_commission(): void
    {
        // Habilitar comisiones (Modules/Commissions escucha ARInvoiceFullyPaid)
        AppSetting::updateOrCreate(
            ['key' => 'commissions.enabled'],
            ['value' => 'true', 'type' => 'boolean', 'group' => 'commissions', 'label' => 'commissions.enabled']
        );
        Cache::forget('app_setting:commissions.enabled');

        $salesperson = User::factory()->create(['commission_pct' => 10.0]);
        $customer = Contact::factory()->customer()->create();

        $order = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'assigned_to' => $salesperson->id,
            'status' => 'confirmed',
            'subtotal' => 1000.00,
            'tax_amount' => 0,
            'discount_total' => 0,
            'total_amount' => 1000.00,
        ]);

        $commission = Commission::where('sales_order_id', $order->id)->first();
        $this->assertNotNull($commission, 'La orden confirmada con vendedor debe crear comision pending');
        $this->assertEquals('pending', $commission->status);

        $invoice = $this->makePostedInvoice(1160.00, [
            'contact_id' => $customer->id,
            'sales_order_id' => $order->id,
        ]);

        $response = $this->registerPayment($invoice, [
            'amount' => 1160.00,
            'forma_pago' => '01',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('invoice.status', 'paid');

        $this->assertEquals(1160.00, $response->json('invoice.paid_amount'));
        $this->assertEquals(0.00, $response->json('invoice.balance'));

        $commission->refresh();
        $this->assertEquals('earned', $commission->status, 'El pago total debe disparar ARInvoiceFullyPaid y ganar la comision');
        $this->assertNotNull($commission->earned_at);
        $this->assertEquals($invoice->id, $commission->ar_invoice_id);
    }

    public function test_two_partial_payments_liquidate_invoice(): void
    {
        $invoice = $this->makePostedInvoice(1000.00);

        $this->registerPayment($invoice, ['amount' => 600.00])->assertStatus(200);

        $response = $this->registerPayment($invoice, ['amount' => 400.00]);

        $response->assertStatus(200)
            ->assertJsonPath('invoice.status', 'paid');

        $this->assertEquals(0.00, $response->json('invoice.balance'));
        $this->assertEquals(2, PaymentApplication::where('ar_invoice_id', $invoice->id)->count());
    }

    public function test_overpayment_returns_422(): void
    {
        $invoice = $this->makePostedInvoice(1000.00, ['paid_amount' => 300.00, 'status' => 'partial']);

        $response = $this->registerPayment($invoice, ['amount' => 800.00]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['amount']);

        $this->assertEquals(300.00, $invoice->fresh()->paid_amount, 'El saldo no debe cambiar');
        $this->assertDatabaseMissing('payment_applications', ['ar_invoice_id' => $invoice->id]);
    }

    public function test_invalid_forma_pago_returns_422(): void
    {
        $invoice = $this->makePostedInvoice(1000.00);

        $response = $this->registerPayment($invoice, ['forma_pago' => 'ZZ']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['forma_pago']);
    }

    public function test_amount_must_be_positive(): void
    {
        $invoice = $this->makePostedInvoice(1000.00);

        $this->registerPayment($invoice, ['amount' => 0])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);

        $this->registerPayment($invoice, ['amount' => -50])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_cannot_register_payment_on_voided_or_cancelled_invoice(): void
    {
        foreach (['voided', 'cancelled', 'void'] as $status) {
            $invoice = $this->makePostedInvoice(1000.00, ['status' => $status]);

            $response = $this->registerPayment($invoice, ['amount' => 100.00]);

            $response->assertStatus(422);
            $response->assertJsonValidationErrors(['invoice']);
        }
    }

    public function test_guest_cannot_register_payment(): void
    {
        $invoice = $this->makePostedInvoice(1000.00);

        $response = $this->postJson("/api/v1/ar-invoices/{$invoice->id}/register-payment", [
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 100.00,
            'forma_pago' => '03',
        ]);

        $response->assertStatus(401);
    }

    public function test_customer_without_permission_gets_403(): void
    {
        $invoice = $this->makePostedInvoice(1000.00);

        $response = $this->actingAs($this->getCustomerUser(), 'sanctum')
            ->postJson("/api/v1/ar-invoices/{$invoice->id}/register-payment", [
                'payment_date' => now()->format('Y-m-d'),
                'amount' => 100.00,
                'forma_pago' => '03',
            ]);

        $response->assertStatus(403);
    }

    public function test_payment_date_and_amount_are_required(): void
    {
        $invoice = $this->makePostedInvoice(1000.00);

        $response = $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson("/api/v1/ar-invoices/{$invoice->id}/register-payment", []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['payment_date', 'amount', 'forma_pago']);
    }
}
