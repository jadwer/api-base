<?php

namespace Modules\Finance\Tests\Feature;

use Modules\Contacts\Models\Contact;
use Modules\Finance\Models\APInvoice;
use Modules\Finance\Models\ARInvoice;
use Modules\Finance\Models\ARPayment;
use Modules\Finance\Models\Payment;
use Tests\TestCase;

/**
 * Paquete B, cierre (2026-09-30): el PATCH ya era inmutable, pero un
 * documento contabilizado o cobrado se podia BORRAR por el API. Ahora solo
 * se borra lo que no tuvo efectos; lo demas se anula con su accion.
 */
class FinancialDeletionTest extends TestCase
{
    private function arInvoice(array $overrides = []): ARInvoice
    {
        return ARInvoice::create(array_merge([
            'invoice_number' => 'AR-' . uniqid(),
            'invoice_date' => '2026-08-01',
            'due_date' => '2026-08-31',
            'contact_id' => Contact::factory()->create(['is_customer' => true])->id,
            'subtotal' => 100,
            'tax_amount' => 16,
            'total_amount' => 116,
            'paid_amount' => 0,
            'status' => 'draft',
        ], $overrides));
    }

    private function deleteDoc(string $type, $id)
    {
        return $this->actingAs($this->getAdminUser(), 'sanctum')->jsonApi()->delete("/api/v1/{$type}/{$id}");
    }

    public function test_posted_ar_invoice_cannot_be_deleted(): void
    {
        $invoice = $this->arInvoice(['status' => 'posted']);

        $response = $this->deleteDoc('ar-invoices', $invoice->id);

        $response->assertStatus(403);
        $this->assertStringContainsString('no se puede eliminar', json_encode($response->json(), JSON_UNESCAPED_UNICODE));
        $this->assertNotNull(ARInvoice::find($invoice->id));
    }

    public function test_draft_with_payments_cannot_be_deleted(): void
    {
        $invoice = $this->arInvoice(['paid_amount' => 50]);

        $this->deleteDoc('ar-invoices', $invoice->id)->assertStatus(403);
    }

    public function test_clean_draft_ar_invoice_can_be_deleted(): void
    {
        $invoice = $this->arInvoice();

        $this->deleteDoc('ar-invoices', $invoice->id)->assertNoContent();
    }

    public function test_paid_ap_invoice_cannot_be_deleted(): void
    {
        $invoice = APInvoice::create([
            'invoice_number' => 'AP-' . uniqid(),
            'invoice_date' => '2026-08-01',
            'due_date' => '2026-08-31',
            'contact_id' => Contact::factory()->create(['is_supplier' => true])->id,
            'subtotal' => 500,
            'tax_amount' => 80,
            'total_amount' => 580,
            'paid_amount' => 580,
            'status' => 'paid',
        ]);

        $this->deleteDoc('ap-invoices', $invoice->id)->assertStatus(403);
    }

    public function test_applied_payment_cannot_be_deleted(): void
    {
        $payment = Payment::factory()->create(['status' => 'applied', 'applied_amount' => 100, 'journal_entry_id' => null]);

        $this->deleteDoc('payments', $payment->id)->assertStatus(403);
    }

    public function test_posted_ar_payment_cannot_be_deleted(): void
    {
        $payment = ARPayment::factory()->create(['status' => 'posted']);

        $this->deleteDoc('ar-payments', $payment->id)->assertStatus(403);
    }
}
