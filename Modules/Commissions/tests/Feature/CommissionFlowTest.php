<?php

namespace Modules\Commissions\Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\AppConfig\Models\AppSetting;
use Modules\Commissions\Models\Commission;
use Modules\Commissions\Services\CommissionService;
use Modules\Contacts\Models\Contact;
use Modules\Finance\Models\ARInvoice;
use Modules\Finance\Models\BankAccount;
use Modules\Finance\Models\Payment;
use Modules\Finance\Models\PaymentApplication;
use Modules\Finance\Models\PaymentMethod;
use Modules\Finance\Services\PaymentApplicationService;
use Modules\Sales\Models\SalesOrder;
use Modules\User\Models\User;
use Tests\TestCase;

/**
 * CommissionFlowTest
 *
 * Covers the WS5 design edge cases end to end, going through the real
 * PaymentApplicationService (events included), the SalesOrder observer and
 * the Commissions listeners.
 */
class CommissionFlowTest extends TestCase
{
    private PaymentApplicationService $paymentApplicationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentApplicationService = app(PaymentApplicationService::class);

        // Open fiscal period so payment GL posting works
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

    private function setAppSetting(string $key, string $value, string $type = 'string'): void
    {
        AppSetting::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type, 'group' => 'commissions', 'label' => $key]
        );

        Cache::forget("app_setting:{$key}");
    }

    private function enableCommissions(): void
    {
        $this->setAppSetting('commissions.enabled', 'true', 'boolean');
    }

    private function createSalesperson(?float $pct = 10.0): User
    {
        return User::factory()->create(['commission_pct' => $pct]);
    }

    /**
     * @return array{0: SalesOrder, 1: Contact}
     */
    private function createOrderWithSalesperson(User $salesperson, float $total = 1000.00): array
    {
        $customer = Contact::factory()->customer()->create();

        $order = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'assigned_to' => $salesperson->id,
            'status' => 'confirmed',
            'subtotal' => $total,
            'tax_amount' => 0,
            'discount_total' => 0,
            'total_amount' => $total,
        ]);

        return [$order, $customer];
    }

    private function createPostedInvoice(SalesOrder $order, Contact $customer, float $total = 1160.00): ARInvoice
    {
        return ARInvoice::factory()->create([
            'contact_id' => $customer->id,
            'sales_order_id' => $order->id,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'currency' => 'MXN',
            'subtotal' => round($total / 1.16, 2),
            'tax_amount' => round($total - $total / 1.16, 2),
            'total_amount' => $total,
            'paid_amount' => 0,
            'status' => 'posted',
            'is_active' => true,
        ]);
    }

    private function createUnappliedPayment(Contact $customer, float $amount): Payment
    {
        return Payment::factory()->create([
            'payment_date' => now(),
            'contact_id' => $customer->id,
            'bank_account_id' => BankAccount::factory()->create()->id,
            'payment_method_id' => PaymentMethod::factory()->create()->id,
            'amount' => $amount,
            'applied_amount' => 0,
            'unapplied_amount' => $amount,
            'status' => 'unapplied',
            'is_active' => true,
        ]);
    }

    /**
     * Edge case 1: partial payment does not earn; later full liquidation does.
     */
    public function test_partial_payment_does_not_earn_but_full_liquidation_does(): void
    {
        $this->enableCommissions();

        $salesperson = $this->createSalesperson(10.0);
        [$order, $customer] = $this->createOrderWithSalesperson($salesperson);
        $invoice = $this->createPostedInvoice($order, $customer, 1160.00);

        $commission = Commission::where('sales_order_id', $order->id)->first();
        $this->assertNotNull($commission, 'Observer should have created a pending commission');
        $this->assertEquals('pending', $commission->status);

        // Partial payment: 500 of 1160
        $payment1 = $this->createUnappliedPayment($customer, 500.00);
        $this->paymentApplicationService->applyPayment($payment1, $invoice->fresh(), 500.00);

        $commission->refresh();
        $this->assertEquals('pending', $commission->status, 'Partial payment must NOT earn the commission');
        $this->assertNull($commission->earned_at);

        // Liquidation: remaining 660
        $payment2 = $this->createUnappliedPayment($customer, 660.00);
        $this->paymentApplicationService->applyPayment($payment2, $invoice->fresh(), 660.00);

        $commission->refresh();
        $this->assertEquals('earned', $commission->status);
        $this->assertNotNull($commission->earned_at);
        $this->assertEquals($invoice->id, $commission->ar_invoice_id);
        // Base recalculated from the invoice total, pct frozen at 10
        $this->assertEquals(1160.00, $commission->base_amount);
        $this->assertEquals(10.0, $commission->commission_pct);
        $this->assertEquals(116.00, $commission->commission_amount);
    }

    /**
     * Edge case 2a: unapply after earned reverts the commission to pending.
     */
    public function test_unapply_after_earned_reverts_commission_to_pending(): void
    {
        $this->enableCommissions();

        $salesperson = $this->createSalesperson(10.0);
        [$order, $customer] = $this->createOrderWithSalesperson($salesperson);
        $invoice = $this->createPostedInvoice($order, $customer, 1160.00);

        $payment = $this->createUnappliedPayment($customer, 1160.00);
        $application = $this->paymentApplicationService->applyPayment($payment, $invoice->fresh(), 1160.00);

        $commission = Commission::where('sales_order_id', $order->id)->firstOrFail();
        $this->assertEquals('earned', $commission->status);

        $this->paymentApplicationService->unapplyPayment(PaymentApplication::findOrFail($application->id));

        $commission->refresh();
        $this->assertEquals('pending', $commission->status);
        $this->assertNull($commission->earned_at);
    }

    /**
     * Edge case 2b: unapply after the commission was already paid must NOT
     * touch it (warning logged, manual adjustment).
     */
    public function test_unapply_after_paid_does_not_touch_commission(): void
    {
        $this->enableCommissions();

        $salesperson = $this->createSalesperson(10.0);
        [$order, $customer] = $this->createOrderWithSalesperson($salesperson);
        $invoice = $this->createPostedInvoice($order, $customer, 1160.00);

        $payment = $this->createUnappliedPayment($customer, 1160.00);
        $application = $this->paymentApplicationService->applyPayment($payment, $invoice->fresh(), 1160.00);

        $commission = Commission::where('sales_order_id', $order->id)->firstOrFail();
        $commission->update([
            'status' => Commission::STATUS_PAID,
            'paid_at' => now(),
            'payment_reference' => 'PAY-TEST-001',
        ]);

        $this->paymentApplicationService->unapplyPayment(PaymentApplication::findOrFail($application->id));

        $commission->refresh();
        $this->assertEquals('paid', $commission->status, 'Paid commissions must never be reverted automatically');
        $this->assertNotNull($commission->paid_at);
        $this->assertEquals('PAY-TEST-001', $commission->payment_reference);
    }

    /**
     * Edge case 3: order without salesperson creates no row; assigning one
     * later creates it.
     */
    public function test_order_without_salesperson_creates_no_commission_until_assigned(): void
    {
        $this->enableCommissions();

        $customer = Contact::factory()->customer()->create();

        $order = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'assigned_to' => null,
            'status' => 'confirmed',
            'total_amount' => 2000.00,
        ]);

        $this->assertDatabaseMissing('commissions', ['sales_order_id' => $order->id]);

        // Salesperson assigned afterwards
        $salesperson = $this->createSalesperson(8.0);
        $order->update(['assigned_to' => $salesperson->id]);

        $commission = Commission::where('sales_order_id', $order->id)->first();
        $this->assertNotNull($commission, 'Assigning a salesperson later must create the pending row');
        $this->assertEquals('pending', $commission->status);
        $this->assertEquals($salesperson->id, $commission->user_id);
        $this->assertEquals(8.0, $commission->commission_pct);
    }

    /**
     * Edge case 3b: contact default_salesperson_id is used when the order
     * has no assigned_to.
     */
    public function test_contact_default_salesperson_is_used_when_order_is_unassigned(): void
    {
        $this->enableCommissions();

        $salesperson = $this->createSalesperson(12.0);
        $customer = Contact::factory()->customer()->create([
            'default_salesperson_id' => $salesperson->id,
        ]);

        $order = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'assigned_to' => null,
            'status' => 'confirmed',
            'total_amount' => 1000.00,
        ]);

        $commission = Commission::where('sales_order_id', $order->id)->first();
        $this->assertNotNull($commission);
        $this->assertEquals($salesperson->id, $commission->user_id);
    }

    /**
     * Edge case 4: changing the salesperson pct after earned does NOT
     * recalculate (pct frozen in the row).
     */
    public function test_pct_change_after_earned_does_not_recalculate(): void
    {
        $this->enableCommissions();

        $salesperson = $this->createSalesperson(10.0);
        [$order, $customer] = $this->createOrderWithSalesperson($salesperson);
        $invoice = $this->createPostedInvoice($order, $customer, 1000.00);

        $payment = $this->createUnappliedPayment($customer, 1000.00);
        $this->paymentApplicationService->applyPayment($payment, $invoice->fresh(), 1000.00);

        $commission = Commission::where('sales_order_id', $order->id)->firstOrFail();
        $this->assertEquals('earned', $commission->status);
        $this->assertEquals(10.0, $commission->commission_pct);
        $this->assertEquals(100.00, $commission->commission_amount);

        // Salesperson pct changes afterwards
        $salesperson->update(['commission_pct' => 20.0]);

        // Re-sync explicitly to prove non-pending rows are never recalculated
        app(CommissionService::class)->syncPendingForOrder($order->fresh());

        $commission->refresh();
        $this->assertEquals(10.0, $commission->commission_pct, 'Pct must stay frozen after earned');
        $this->assertEquals(100.00, $commission->commission_amount);
    }

    /**
     * Edge case 6: commissions.enabled=false, everything is a no-op.
     */
    public function test_disabled_feature_makes_observer_and_listeners_noop(): void
    {
        // commissions.enabled not set: getBoolean default false

        $salesperson = $this->createSalesperson(10.0);
        [$order, $customer] = $this->createOrderWithSalesperson($salesperson);

        $this->assertDatabaseMissing('commissions', ['sales_order_id' => $order->id]);

        // Full payment: listener must also be a no-op
        $invoice = $this->createPostedInvoice($order, $customer, 1160.00);
        $payment = $this->createUnappliedPayment($customer, 1160.00);
        $this->paymentApplicationService->applyPayment($payment, $invoice->fresh(), 1160.00);

        $this->assertEquals('paid', $invoice->fresh()->status);
        $this->assertDatabaseMissing('commissions', ['sales_order_id' => $order->id]);
    }

    /**
     * Cancelled order cancels the commission unless it is already paid.
     */
    public function test_cancelled_order_cancels_commission_unless_paid(): void
    {
        $this->enableCommissions();

        $salesperson = $this->createSalesperson(10.0);
        [$order] = $this->createOrderWithSalesperson($salesperson);

        $commission = Commission::where('sales_order_id', $order->id)->firstOrFail();
        $this->assertEquals('pending', $commission->status);

        $order->update(['status' => 'cancelled']);
        $this->assertEquals('cancelled', $commission->fresh()->status);

        // Paid commissions survive order cancellation
        [$order2] = $this->createOrderWithSalesperson($salesperson);
        $commission2 = Commission::where('sales_order_id', $order2->id)->firstOrFail();
        $commission2->update(['status' => Commission::STATUS_PAID, 'paid_at' => now()]);

        $order2->update(['status' => 'cancelled']);
        $this->assertEquals('paid', $commission2->fresh()->status);
    }

    /**
     * Pct resolution: contact override > user pct > AppSetting default.
     */
    public function test_resolve_pct_precedence(): void
    {
        $this->enableCommissions();
        $this->setAppSetting('commissions.default_pct', '5.0');

        $service = app(CommissionService::class);

        $userWithPct = User::factory()->create(['commission_pct' => 7.5]);
        $userWithoutPct = User::factory()->create(['commission_pct' => null]);
        $contactWithOverride = Contact::factory()->customer()->create(['commission_pct_override' => 3.0]);
        $contactWithoutOverride = Contact::factory()->customer()->create();

        $this->assertEquals(3.0, $service->resolvePct($contactWithOverride, $userWithPct));
        $this->assertEquals(7.5, $service->resolvePct($contactWithoutOverride, $userWithPct));
        $this->assertEquals(5.0, $service->resolvePct($contactWithoutOverride, $userWithoutPct));
    }
}
