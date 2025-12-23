<?php

namespace Modules\Finance\Tests\Integration;

use Tests\TestCase;
use Modules\Finance\Models\ARInvoice;
use Modules\Finance\Services\ARInvoiceService;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\Account;
use Modules\Contacts\Models\Contact;

/**
 * ARInvoiceGLPostingTest
 *
 * Tests de integración Finance → Accounting
 * Verifica que la creación de AR Invoices genere correctamente los Journal Entries
 */
class ARInvoiceGLPostingTest extends TestCase
{
    private ARInvoiceService $arInvoiceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->arInvoiceService = app(ARInvoiceService::class);
    }

    public function test_creating_ar_invoice_creates_journal_entry(): void
    {
        // Arrange: Crear customer y asegurar que existen las GL accounts
        $customer = Contact::factory()->customer()->create();

        // Act: Crear AR Invoice usando el service
        $invoice = $this->arInvoiceService->createInvoice([
            'invoiceDate' => now()->format('Y-m-d'),
            'dueDate' => now()->addDays(30)->format('Y-m-d'),
            'contactId' => $customer->id,
            'currency' => 'MXN',
            'subtotal' => 1000.00,
            'taxAmount' => 160.00,
            'totalAmount' => 1160.00,
        ]);

        // Assert: Verificar que se creó el invoice
        $this->assertInstanceOf(ARInvoice::class, $invoice);
        $this->assertEquals('MXN', $invoice->currency);
        $this->assertEquals(1160.00, $invoice->total_amount);
        $this->assertEquals(0, $invoice->paid_amount);
        $this->assertEquals('posted', $invoice->status);

        // Assert: Verificar que se vinculó un JournalEntry
        $this->assertNotNull($invoice->journal_entry_id);
        $this->assertInstanceOf(JournalEntry::class, $invoice->journalEntry);

        // Assert: Verificar que el invoice number fue generado
        $this->assertStringStartsWith('AR-', $invoice->invoice_number);
    }

    public function test_journal_entry_balances_correctly(): void
    {
        // Arrange
        $customer = Contact::factory()->customer()->create();

        // Act: Crear AR Invoice
        $invoice = $this->arInvoiceService->createInvoice([
            'invoiceDate' => now()->format('Y-m-d'),
            'dueDate' => now()->addDays(30)->format('Y-m-d'),
            'contactId' => $customer->id,
            'currency' => 'MXN',
            'subtotal' => 1000.00,
            'taxAmount' => 160.00,
            'totalAmount' => 1160.00,
        ]);

        $journalEntry = $invoice->journalEntry;

        // Assert: Verificar que tiene exactamente 2 líneas (DR y CR)
        $this->assertCount(2, $journalEntry->journalLines);

        // Assert: Verificar que balancea (total debit = total credit)
        $totalDebit = $journalEntry->journalLines->sum('debit');
        $totalCredit = $journalEntry->journalLines->sum('credit');

        $this->assertEquals($totalDebit, $totalCredit, 'Journal entry debe balancear');
        $this->assertEquals(1160.00, $totalDebit, 'Debit debe ser igual al total de la invoice');
        $this->assertEquals(1160.00, $totalCredit, 'Credit debe ser igual al total de la invoice');
    }

    public function test_journal_entry_uses_correct_gl_accounts(): void
    {
        // Arrange
        $customer = Contact::factory()->customer()->create();
        $customerAccount = Account::where('code', '1104')->first();
        $revenueAccount = Account::where('code', '4101')->first();

        // Act
        $invoice = $this->arInvoiceService->createInvoice([
            'invoiceDate' => now()->format('Y-m-d'),
            'dueDate' => now()->addDays(30)->format('Y-m-d'),
            'contactId' => $customer->id,
            'currency' => 'MXN',
            'subtotal' => 1000.00,
            'taxAmount' => 160.00,
            'totalAmount' => 1160.00,
        ]);

        $journalEntry = $invoice->journalEntry;

        // Assert: Verificar que usa la cuenta correcta de Clientes (DR)
        $debitLine = $journalEntry->journalLines->where('debit', '>', 0)->first();
        $this->assertEquals($customerAccount->id, $debitLine->account_id);
        $this->assertEquals(1160.00, $debitLine->debit);
        $this->assertEquals(0, $debitLine->credit);

        // Assert: Verificar que usa la cuenta correcta de Ingresos (CR)
        $creditLine = $journalEntry->journalLines->where('credit', '>', 0)->first();
        $this->assertEquals($revenueAccount->id, $creditLine->account_id);
        $this->assertEquals(0, $creditLine->debit);
        $this->assertEquals(1160.00, $creditLine->credit);
    }

    public function test_journal_entry_has_correct_metadata(): void
    {
        // Arrange
        $customer = Contact::factory()->customer()->create();

        // Act
        $invoice = $this->arInvoiceService->createInvoice([
            'invoiceDate' => now()->format('Y-m-d'),
            'dueDate' => now()->addDays(30)->format('Y-m-d'),
            'contactId' => $customer->id,
            'currency' => 'MXN',
            'subtotal' => 1000.00,
            'taxAmount' => 160.00,
            'totalAmount' => 1160.00,
        ]);

        $journalEntry = $invoice->journalEntry;

        // Assert: Verificar que el journal entry tiene la fecha correcta
        $this->assertEquals(now()->format('Y-m-d'), $journalEntry->date->format('Y-m-d'));

        // Assert: Verificar que tiene la referencia al invoice number
        $this->assertEquals($invoice->invoice_number, $journalEntry->reference);

        // Assert: Verificar que la descripción menciona el invoice
        $this->assertStringContainsString($invoice->invoice_number, $journalEntry->description);
        $this->assertStringContainsString('AR Invoice', $journalEntry->description);
    }

    public function test_creating_invoice_without_gl_accounts_fails(): void
    {
        // Arrange: Eliminar las GL accounts requeridas
        Account::where('code', '1104')->delete();

        $customer = Contact::factory()->customer()->create();

        // Act & Assert: Debe fallar con mensaje claro
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('GL Account for Customers (1104) not found');

        $this->arInvoiceService->createInvoice([
            'invoiceDate' => now()->format('Y-m-d'),
            'dueDate' => now()->addDays(30)->format('Y-m-d'),
            'contactId' => $customer->id,
            'currency' => 'MXN',
            'subtotal' => 1000.00,
            'taxAmount' => 160.00,
            'totalAmount' => 1160.00,
        ]);
    }

    public function test_multiple_invoices_create_separate_journal_entries(): void
    {
        // Arrange
        $customer = Contact::factory()->customer()->create();

        // Act: Crear 3 invoices (todas en el mismo mes para evitar problemas con fiscal periods)
        $invoice1 = $this->arInvoiceService->createInvoice([
            'invoiceDate' => now()->format('Y-m-d'),
            'dueDate' => now()->addDays(30)->format('Y-m-d'),
            'contactId' => $customer->id,
            'currency' => 'MXN',
            'subtotal' => 1000.00,
            'taxAmount' => 160.00,
            'totalAmount' => 1160.00,
        ]);

        $invoice2 = $this->arInvoiceService->createInvoice([
            'invoiceDate' => now()->format('Y-m-d'),
            'dueDate' => now()->addDays(45)->format('Y-m-d'),
            'contactId' => $customer->id,
            'currency' => 'MXN',
            'subtotal' => 2000.00,
            'taxAmount' => 320.00,
            'totalAmount' => 2320.00,
        ]);

        $invoice3 = $this->arInvoiceService->createInvoice([
            'invoiceDate' => now()->format('Y-m-d'),
            'dueDate' => now()->addDays(60)->format('Y-m-d'),
            'contactId' => $customer->id,
            'currency' => 'MXN',
            'subtotal' => 500.00,
            'taxAmount' => 80.00,
            'totalAmount' => 580.00,
        ]);

        // Assert: Cada invoice debe tener su propio journal entry
        $this->assertNotEquals($invoice1->journal_entry_id, $invoice2->journal_entry_id);
        $this->assertNotEquals($invoice2->journal_entry_id, $invoice3->journal_entry_id);
        $this->assertNotEquals($invoice1->journal_entry_id, $invoice3->journal_entry_id);

        // Assert: Verificar que los números de invoice son secuenciales
        $this->assertEquals('AR-000001', $invoice1->invoice_number);
        $this->assertEquals('AR-000002', $invoice2->invoice_number);
        $this->assertEquals('AR-000003', $invoice3->invoice_number);
    }

    public function test_invoice_helper_methods_work_correctly(): void
    {
        // Arrange
        $customer = Contact::factory()->customer()->create();

        // Act
        $invoice = $this->arInvoiceService->createInvoice([
            'invoiceDate' => now()->format('Y-m-d'),
            'dueDate' => now()->addDays(30)->format('Y-m-d'),
            'contactId' => $customer->id,
            'currency' => 'MXN',
            'subtotal' => 1000.00,
            'taxAmount' => 160.00,
            'totalAmount' => 1160.00,
        ]);

        // Assert: Remaining balance debe ser el total (no hay pagos aplicados)
        $remainingBalance = $this->arInvoiceService->calculateRemainingBalance($invoice);
        $this->assertEquals(1160.00, $remainingBalance);

        // Assert: No debe estar fully paid
        $this->assertFalse($this->arInvoiceService->isFullyPaid($invoice));

        // Assert: No debe estar overdue (due date es futuro)
        $this->assertFalse($this->arInvoiceService->isOverdue($invoice));

        // Act: Simular pago parcial
        $invoice->update(['paid_amount' => 500.00]);
        $invoice->refresh();

        // Assert: Remaining balance debe actualizarse
        $remainingBalance = $this->arInvoiceService->calculateRemainingBalance($invoice);
        $this->assertEquals(660.00, $remainingBalance);
        $this->assertFalse($this->arInvoiceService->isFullyPaid($invoice));

        // Act: Simular pago completo
        $invoice->update(['paid_amount' => 1160.00]);
        $invoice->refresh();

        // Assert: Debe estar fully paid
        $this->assertTrue($this->arInvoiceService->isFullyPaid($invoice));
        $this->assertEquals(0, $this->arInvoiceService->calculateRemainingBalance($invoice));
    }
}
