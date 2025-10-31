<?php

namespace Modules\Billing\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Billing\Models\CFDIInvoice;

class CFDIInvoiceDestroyTest extends TestCase
{
    // NO RefreshDatabase - violates Mandamiento #5

    public function test_admin_can_delete_cfdi_invoice()
    {
        $user = $this->getAdminUser();
        $invoice = CFDIInvoice::factory()->draft()->create();

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/cfdi-invoices/' . $invoice->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('cfdi_invoices', [
            'id' => $invoice->id,
        ]);
    }

    public function test_tech_cannot_delete_cfdi_invoice()
    {
        $user = $this->getTechUser();
        $invoice = CFDIInvoice::factory()->create();

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/cfdi-invoices/' . $invoice->id);

        $response->assertStatus(403);

        $this->assertDatabaseHas('cfdi_invoices', [
            'id' => $invoice->id,
        ]);
    }

    public function test_customer_cannot_delete_cfdi_invoice()
    {
        $user = $this->getCustomerUser();
        $invoice = CFDIInvoice::factory()->create();

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/cfdi-invoices/' . $invoice->id);

        $response->assertStatus(403);

        $this->assertDatabaseHas('cfdi_invoices', [
            'id' => $invoice->id,
        ]);
    }

    public function test_guest_cannot_delete_cfdi_invoice()
    {
        $invoice = CFDIInvoice::factory()->create();

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->delete('/api/v1/cfdi-invoices/' . $invoice->id);

        $response->assertStatus(401);

        $this->assertDatabaseHas('cfdi_invoices', [
            'id' => $invoice->id,
        ]);
    }

    public function test_returns_404_when_deleting_nonexistent_invoice()
    {
        $user = $this->getAdminUser();

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/cfdi-invoices/99999');

        $response->assertStatus(404);
    }

    public function test_can_delete_draft_invoice()
    {
        $user = $this->getAdminUser();
        $invoice = CFDIInvoice::factory()->draft()->create();

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/cfdi-invoices/' . $invoice->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('cfdi_invoices', [
            'id' => $invoice->id,
        ]);
    }

    public function test_can_delete_valid_invoice()
    {
        $user = $this->getAdminUser();
        $invoice = CFDIInvoice::factory()->valid()->create();

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/cfdi-invoices/' . $invoice->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('cfdi_invoices', [
            'id' => $invoice->id,
        ]);
    }

    public function test_can_delete_cancelled_invoice()
    {
        $user = $this->getAdminUser();
        $invoice = CFDIInvoice::factory()->cancelled()->create();

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/cfdi-invoices/' . $invoice->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('cfdi_invoices', [
            'id' => $invoice->id,
        ]);
    }

    public function test_can_delete_invoice_with_discount()
    {
        $user = $this->getAdminUser();
        $invoice = CFDIInvoice::factory()->withDiscount()->create();

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/cfdi-invoices/' . $invoice->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('cfdi_invoices', [
            'id' => $invoice->id,
        ]);
    }

    public function test_can_delete_egreso_invoice()
    {
        $user = $this->getAdminUser();
        $invoice = CFDIInvoice::factory()->egreso()->create();

        $response = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/cfdi-invoices/' . $invoice->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('cfdi_invoices', [
            'id' => $invoice->id,
        ]);
    }

    public function test_deleting_same_invoice_twice_returns_404()
    {
        $user = $this->getAdminUser();
        $invoice = CFDIInvoice::factory()->create();

        // First deletion
        $response1 = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/cfdi-invoices/' . $invoice->id);

        $response1->assertNoContent();

        // Second deletion attempt
        $response2 = $this->jsonApi()
            ->expects('cfdi-invoices')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/cfdi-invoices/' . $invoice->id);

        $response2->assertStatus(404);
    }
}
