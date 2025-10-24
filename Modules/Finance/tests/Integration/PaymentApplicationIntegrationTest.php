<?php

namespace Modules\Finance\Tests\Integration;

use Tests\TestCase;
use Modules\Finance\Models\Payment;
use Modules\Finance\Models\ARInvoice;
use Modules\Finance\Models\PaymentApplication;
use Modules\Finance\Models\BankAccount;
use Modules\Finance\Models\PaymentMethod;
use Modules\Finance\Services\ARInvoiceService;
use Modules\Finance\Services\PaymentApplicationService;
use Modules\Accounting\Models\Account;
use Modules\Contacts\Models\Contact;

/**
 * PaymentApplicationIntegrationTest
 *
 * Tests de integración para la aplicación de pagos a invoices
 * Verifica la lógica de negocio completa y el GL posting
 */
class PaymentApplicationIntegrationTest extends TestCase
{
    private ARInvoiceService $arInvoiceService;
    private PaymentApplicationService $paymentApplicationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->arInvoiceService = app(ARInvoiceService::class);
        $this->paymentApplicationService = app(PaymentApplicationService::class);
    }

    public function test_applying_payment_to_invoice_updates_balances(): void
    {
        // Arrange: Crear invoice y payment
        $customer = Contact::factory()->customer()->create();
        $bankAccount = BankAccount::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        $invoice = $this->arInvoiceService->createInvoice([
            'invoiceDate' => '2025-01-15',
            'dueDate' => '2025-02-15',
            'customerId' => $customer->id,
            'currency' => 'MXN',
            'subtotal' => 1000.00,
            'taxAmount' => 160.00,
            'totalAmount' => 1160.00,
        ]);

        $payment = Payment::factory()->create([
            'customer_id' => $customer->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method_id' => $paymentMethod->id,
            'amount' => 1160.00,
            'applied_amount' => 0,
            'unapplied_amount' => 1160.00,
            'status' => 'unapplied',
        ]);

        // Act: Aplicar payment a invoice
        $application = $this->paymentApplicationService->applyPayment($payment, $invoice, 1160.00);

        // Assert: Verificar que se creó la application
        $this->assertInstanceOf(PaymentApplication::class, $application);
        $this->assertEquals(1160.00, $application->amount);

        // Assert: Verificar que se actualizó el invoice
        $invoice->refresh();
        $this->assertEquals(1160.00, $invoice->paid_amount);
        $this->assertEquals('paid', $invoice->status);

        // Assert: Verificar que se actualizó el payment
        $payment->refresh();
        $this->assertEquals(1160.00, $payment->applied_amount);
        $this->assertEquals(0, $payment->unapplied_amount);
        $this->assertEquals('applied', $payment->status);
    }

    public function test_partial_payment_application(): void
    {
        // Arrange
        $customer = Contact::factory()->customer()->create();
        $bankAccount = BankAccount::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        $invoice = $this->arInvoiceService->createInvoice([
            'invoiceDate' => '2025-01-15',
            'dueDate' => '2025-02-15',
            'customerId' => $customer->id,
            'currency' => 'MXN',
            'subtotal' => 1000.00,
            'taxAmount' => 160.00,
            'totalAmount' => 1160.00,
        ]);

        $payment = Payment::factory()->create([
            'customer_id' => $customer->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method_id' => $paymentMethod->id,
            'amount' => 600.00,
            'applied_amount' => 0,
            'unapplied_amount' => 600.00,
            'status' => 'unapplied',
        ]);

        // Act: Aplicar pago parcial
        $this->paymentApplicationService->applyPayment($payment, $invoice, 600.00);

        // Assert: Invoice debe estar parcialmente pagada
        $invoice->refresh();
        $this->assertEquals(600.00, $invoice->paid_amount);
        $this->assertEquals('partial', $invoice->status);

        // Assert: Payment debe estar completamente aplicado
        $payment->refresh();
        $this->assertEquals(600.00, $payment->applied_amount);
        $this->assertEquals(0, $payment->unapplied_amount);
        $this->assertEquals('applied', $payment->status);

        // Assert: Remaining balance de la invoice
        $this->assertEquals(560.00, $this->arInvoiceService->calculateRemainingBalance($invoice));
    }

    public function test_payment_application_creates_gl_entry(): void
    {
        // Arrange
        $customer = Contact::factory()->customer()->create();
        $bankAccount = BankAccount::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        $invoice = $this->arInvoiceService->createInvoice([
            'invoiceDate' => '2025-01-15',
            'dueDate' => '2025-02-15',
            'customerId' => $customer->id,
            'currency' => 'MXN',
            'subtotal' => 1000.00,
            'taxAmount' => 160.00,
            'totalAmount' => 1160.00,
        ]);

        $payment = Payment::factory()->create([
            'customer_id' => $customer->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method_id' => $paymentMethod->id,
            'amount' => 1160.00,
            'applied_amount' => 0,
            'unapplied_amount' => 1160.00,
            'status' => 'unapplied',
        ]);

        // Act
        $this->paymentApplicationService->applyPayment($payment, $invoice, 1160.00);

        // Assert: Payment debe tener journal_entry_id
        $payment->refresh();
        $this->assertNotNull($payment->journal_entry_id);

        // Assert: Journal entry debe balancear
        $journalEntry = $payment->journalEntry;
        $this->assertCount(2, $journalEntry->lines);

        $totalDebit = $journalEntry->lines->sum('debit_amount');
        $totalCredit = $journalEntry->lines->sum('credit_amount');
        $this->assertEquals($totalDebit, $totalCredit);
        $this->assertEquals(1160.00, $totalDebit);
    }

    public function test_payment_application_uses_correct_gl_accounts(): void
    {
        // Arrange
        $customer = Contact::factory()->customer()->create();
        $bankAccount = BankAccount::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        $bankGLAccount = Account::where('code', '1020')->first();
        $customerGLAccount = Account::where('code', '1100')->first();

        $invoice = $this->arInvoiceService->createInvoice([
            'invoiceDate' => '2025-01-15',
            'dueDate' => '2025-02-15',
            'customerId' => $customer->id,
            'currency' => 'MXN',
            'subtotal' => 1000.00,
            'taxAmount' => 160.00,
            'totalAmount' => 1160.00,
        ]);

        $payment = Payment::factory()->create([
            'customer_id' => $customer->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method_id' => $paymentMethod->id,
            'amount' => 1160.00,
            'applied_amount' => 0,
            'unapplied_amount' => 1160.00,
            'status' => 'unapplied',
        ]);

        // Act
        $this->paymentApplicationService->applyPayment($payment, $invoice, 1160.00);

        // Assert: Verificar GL accounts usadas
        $payment->refresh();
        $journalEntry = $payment->journalEntry;

        $debitLine = $journalEntry->lines->where('debit_amount', '>', 0)->first();
        $this->assertEquals($bankGLAccount->id, $debitLine->account_id);

        $creditLine = $journalEntry->lines->where('credit_amount', '>', 0)->first();
        $this->assertEquals($customerGLAccount->id, $creditLine->account_id);
    }

    public function test_cannot_apply_more_than_invoice_balance(): void
    {
        // Arrange
        $customer = Contact::factory()->customer()->create();
        $bankAccount = BankAccount::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        $invoice = $this->arInvoiceService->createInvoice([
            'invoiceDate' => '2025-01-15',
            'dueDate' => '2025-02-15',
            'customerId' => $customer->id,
            'currency' => 'MXN',
            'subtotal' => 1000.00,
            'taxAmount' => 160.00,
            'totalAmount' => 1160.00,
        ]);

        $payment = Payment::factory()->create([
            'customer_id' => $customer->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method_id' => $paymentMethod->id,
            'amount' => 2000.00,
            'applied_amount' => 0,
            'unapplied_amount' => 2000.00,
            'status' => 'unapplied',
        ]);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('exceeds invoice remaining balance');

        $this->paymentApplicationService->applyPayment($payment, $invoice, 1500.00);
    }

    public function test_cannot_apply_more_than_unapplied_payment_balance(): void
    {
        // Arrange
        $customer = Contact::factory()->customer()->create();
        $bankAccount = BankAccount::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        $invoice = $this->arInvoiceService->createInvoice([
            'invoiceDate' => '2025-01-15',
            'dueDate' => '2025-02-15',
            'customerId' => $customer->id,
            'currency' => 'MXN',
            'subtotal' => 1000.00,
            'taxAmount' => 160.00,
            'totalAmount' => 1160.00,
        ]);

        $payment = Payment::factory()->create([
            'customer_id' => $customer->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method_id' => $paymentMethod->id,
            'amount' => 500.00,
            'applied_amount' => 0,
            'unapplied_amount' => 500.00,
            'status' => 'unapplied',
        ]);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('exceeds unapplied payment balance');

        $this->paymentApplicationService->applyPayment($payment, $invoice, 600.00);
    }

    public function test_cannot_apply_payment_to_different_customer(): void
    {
        // Arrange
        $customer1 = Contact::factory()->customer()->create();
        $customer2 = Contact::factory()->customer()->create();
        $bankAccount = BankAccount::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        $invoice = $this->arInvoiceService->createInvoice([
            'invoiceDate' => '2025-01-15',
            'dueDate' => '2025-02-15',
            'customerId' => $customer1->id,
            'currency' => 'MXN',
            'subtotal' => 1000.00,
            'taxAmount' => 160.00,
            'totalAmount' => 1160.00,
        ]);

        $payment = Payment::factory()->create([
            'customer_id' => $customer2->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method_id' => $paymentMethod->id,
            'amount' => 1160.00,
            'applied_amount' => 0,
            'unapplied_amount' => 1160.00,
            'status' => 'unapplied',
        ]);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('does not match invoice customer');

        $this->paymentApplicationService->applyPayment($payment, $invoice, 1160.00);
    }

    public function test_unapply_payment_reverses_balances(): void
    {
        // Arrange
        $customer = Contact::factory()->customer()->create();
        $bankAccount = BankAccount::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();

        $invoice = $this->arInvoiceService->createInvoice([
            'invoiceDate' => '2025-01-15',
            'dueDate' => '2025-02-15',
            'customerId' => $customer->id,
            'currency' => 'MXN',
            'subtotal' => 1000.00,
            'taxAmount' => 160.00,
            'totalAmount' => 1160.00,
        ]);

        $payment = Payment::factory()->create([
            'customer_id' => $customer->id,
            'bank_account_id' => $bankAccount->id,
            'payment_method_id' => $paymentMethod->id,
            'amount' => 1160.00,
            'applied_amount' => 0,
            'unapplied_amount' => 1160.00,
            'status' => 'unapplied',
        ]);

        $application = $this->paymentApplicationService->applyPayment($payment, $invoice, 1160.00);

        // Act: Unapply payment
        $this->paymentApplicationService->unapplyPayment($application);

        // Assert: Invoice debe volver a estado no pagado
        $invoice->refresh();
        $this->assertEquals(0, $invoice->paid_amount);
        $this->assertEquals('posted', $invoice->status);

        // Assert: Payment debe volver a no aplicado
        $payment->refresh();
        $this->assertEquals(0, $payment->applied_amount);
        $this->assertEquals(1160.00, $payment->unapplied_amount);
        $this->assertEquals('unapplied', $payment->status);

        // Assert: Application debe estar inactiva
        $application->refresh();
        $this->assertFalse($application->is_active);
    }
}
