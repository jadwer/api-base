<?php

namespace Modules\Finance\Tests\Feature;

use Modules\Contacts\Models\Contact;
use Modules\Finance\Models\APInvoice;
use Modules\Finance\Models\ARInvoice;
use Modules\Finance\Models\Payment;
use Tests\TestCase;

/**
 * Paquete B: un documento financiero con efectos contables o cobros es
 * inmutable por PATCH (solo notes/metadata), y el estado JAMAS cambia por
 * edicion directa. Antes se podia reescribir una factura pagada y
 * descuadrar el GL sin rastro.
 */
class FinancialImmutabilityTest extends TestCase
{
    protected function makeARInvoice(array $overrides = []): ARInvoice
    {
        $contact = Contact::factory()->create(['is_customer' => true]);

        return ARInvoice::create(array_merge([
            'invoice_number' => 'AR-' . uniqid(),
            'invoice_date' => '2026-08-01',
            'due_date' => '2026-08-31',
            'contact_id' => $contact->id,
            'subtotal' => 100,
            'tax_amount' => 16,
            'total_amount' => 116,
            'paid_amount' => 0,
            'status' => 'posted',
        ], $overrides));
    }

    protected function patchAR(ARInvoice $invoice, array $attributes)
    {
        return $this->actingAs($this->getAdminUser(), 'sanctum')
            ->jsonApi()
            ->expects('ar-invoices')
            ->withData([
                'type' => 'ar-invoices',
                'id' => (string) $invoice->id,
                'attributes' => $attributes,
            ])
            ->patch("/api/v1/ar-invoices/{$invoice->id}");
    }

    public function test_paid_ar_invoice_rejects_amount_changes(): void
    {
        $invoice = $this->makeARInvoice(['status' => 'paid', 'paid_amount' => 116]);

        $response = $this->patchAR($invoice, [
            'invoiceNumber' => $invoice->invoice_number,
            'invoiceDate' => '2026-08-01',
            'dueDate' => '2026-08-31',
            'contactId' => $invoice->contact_id,
            'subtotal' => 100,
            'taxAmount' => 16,
            'totalAmount' => 999999,
        ]);

        $response->assertStatus(422);
        $this->assertEquals(116.0, (float) $invoice->fresh()->total_amount);
    }

    public function test_paid_ar_invoice_allows_notes_update(): void
    {
        $invoice = $this->makeARInvoice(['status' => 'paid', 'paid_amount' => 116]);

        $this->patchAR($invoice, [
            'invoiceNumber' => $invoice->invoice_number,
            'invoiceDate' => '2026-08-01',
            'dueDate' => '2026-08-31',
            'contactId' => $invoice->contact_id,
            'subtotal' => 100,
            'taxAmount' => 16,
            'totalAmount' => 116,
            'notes' => 'Aclaracion del cobro',
        ])->assertSuccessful();

        $this->assertEquals('Aclaracion del cobro', $invoice->fresh()->notes);
    }

    public function test_resending_same_values_does_not_conflict(): void
    {
        // Los clientes JSON:API reenvian el objeto completo: mismos valores
        // en distinta representacion (fechas ISO, numeros como string) NO
        // deben fallar.
        $invoice = $this->makeARInvoice(['status' => 'posted']);

        $this->patchAR($invoice, [
            'invoiceNumber' => $invoice->invoice_number,
            'invoiceDate' => '2026-08-01T00:00:00.000000Z',
            'dueDate' => '2026-08-31',
            'contactId' => $invoice->contact_id,
            'subtotal' => 100.0,
            'taxAmount' => 16,
            'totalAmount' => 116,
            'notes' => 'ok',
        ])->assertSuccessful();
    }

    public function test_draft_ar_invoice_is_still_editable(): void
    {
        $invoice = $this->makeARInvoice(['status' => 'draft']);

        $this->patchAR($invoice, [
            'invoiceNumber' => $invoice->invoice_number,
            'invoiceDate' => '2026-08-01',
            'dueDate' => '2026-09-15',
            'contactId' => $invoice->contact_id,
            'subtotal' => 200,
            'taxAmount' => 32,
            'totalAmount' => 232,
        ])->assertSuccessful();

        $this->assertEquals(232.0, (float) $invoice->fresh()->total_amount);
    }

    public function test_status_never_changes_via_patch(): void
    {
        $invoice = $this->makeARInvoice(['status' => 'draft']);

        $response = $this->patchAR($invoice, [
            'invoiceNumber' => $invoice->invoice_number,
            'invoiceDate' => '2026-08-01',
            'dueDate' => '2026-08-31',
            'contactId' => $invoice->contact_id,
            'subtotal' => 100,
            'taxAmount' => 16,
            'totalAmount' => 116,
            'status' => 'paid',
        ]);

        $response->assertStatus(422);
        $this->assertEquals('draft', $invoice->fresh()->status);
    }

    public function test_invalid_status_rejected_on_create(): void
    {
        $contact = Contact::factory()->create(['is_customer' => true]);

        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->jsonApi()
            ->expects('ar-invoices')
            ->withData([
                'type' => 'ar-invoices',
                'attributes' => [
                    'invoiceNumber' => 'AR-STATUS-X',
                    'invoiceDate' => '2026-08-01',
                    'dueDate' => '2026-08-31',
                    'contactId' => $contact->id,
                    'subtotal' => 100,
                    'taxAmount' => 16,
                    'totalAmount' => 116,
                    'status' => 'pagadisima',
                ],
            ])
            ->post('/api/v1/ar-invoices')
            ->assertStatus(422);
    }

    public function test_posted_ap_invoice_rejects_amount_changes(): void
    {
        $supplier = Contact::factory()->create(['is_supplier' => true]);
        $invoice = APInvoice::create([
            'invoice_number' => 'AP-' . uniqid(),
            'invoice_date' => '2026-08-01',
            'due_date' => '2026-08-31',
            'contact_id' => $supplier->id,
            'subtotal' => 500,
            'tax_amount' => 80,
            'total_amount' => 580,
            'status' => 'posted',
        ]);

        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->jsonApi()
            ->expects('ap-invoices')
            ->withData([
                'type' => 'ap-invoices',
                'id' => (string) $invoice->id,
                'attributes' => [
                    'invoiceNumber' => $invoice->invoice_number,
                    'invoiceDate' => '2026-08-01',
                    'dueDate' => '2026-08-31',
                    'contactId' => $supplier->id,
                    'subtotal' => 1,
                    'taxAmount' => 80,
                    'totalAmount' => 580,
                ],
            ])
            ->patch("/api/v1/ap-invoices/{$invoice->id}")
            ->assertStatus(422);

        $this->assertEquals(500.0, (float) $invoice->fresh()->subtotal);
    }

    public function test_applied_payment_rejects_amount_change_but_unapplied_allows(): void
    {
        $contact = Contact::factory()->create(['is_customer' => true]);
        $glAccount = \Modules\Accounting\Models\Account::create([
            'code' => 'TST-' . uniqid(),
            'name' => 'Bancos Test',
            'account_type' => 'asset',
            'level' => 1,
            'is_active' => true,
        ]);
        $bank = \Modules\Finance\Models\BankAccount::create([
            'account_name' => 'CAJA-TEST',
            'account_number' => '000123',
            'bank_name' => 'Banco Test',
            'currency' => 'MXN',
            'gl_account_id' => $glAccount->id,
        ]);
        $method = \Modules\Finance\Models\PaymentMethod::create([
            'name' => 'Transferencia Test',
            'type' => 'transfer',
            'code' => 'TRF-' . uniqid(),
            'is_active' => true,
        ]);
        $base = [
            'payment_number' => 'PAY-' . uniqid(),
            'payment_date' => '2026-08-01',
            'contact_id' => $contact->id,
            'bank_account_id' => $bank->id,
            'payment_method_id' => $method->id,
            'amount' => 1000,
            'applied_amount' => 0,
            'unapplied_amount' => 1000,
        ];

        $applied = Payment::create(array_merge($base, [
            'payment_number' => 'PAY-A-' . uniqid(),
            'status' => 'applied',
            'applied_amount' => 1000,
            'unapplied_amount' => 0,
        ]));
        $unapplied = Payment::create(array_merge($base, ['status' => 'unapplied']));

        $patch = fn ($payment, $amount) => $this->actingAs($this->getAdminUser(), 'sanctum')
            ->jsonApi()
            ->expects('payments')
            ->withData([
                'type' => 'payments',
                'id' => (string) $payment->id,
                'attributes' => ['amount' => $amount],
            ])
            ->patch("/api/v1/payments/{$payment->id}");

        $patch($applied, 5)->assertStatus(422);
        $this->assertEquals(1000.0, (float) $applied->fresh()->amount);

        $patch($unapplied, 900)->assertSuccessful();
        $this->assertEquals(900.0, (float) $unapplied->fresh()->amount);
    }
}
