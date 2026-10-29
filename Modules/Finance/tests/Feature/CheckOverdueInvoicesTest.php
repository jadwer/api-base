<?php

namespace Modules\Finance\Tests\Feature;

use Carbon\Carbon;
use Modules\Contacts\Models\Contact;
use Modules\Finance\Models\APInvoice;
use Modules\Finance\Models\ARInvoice;
use Tests\TestCase;

/**
 * Auditoria finanzas 2026-10, punto 3: el cron finance:check-overdue solo
 * excluia paid/overdue/cancelled y pasaba a "overdue" facturas anuladas
 * (void/voided) y borradores, reviviendolas como cobrables.
 */
class CheckOverdueInvoicesTest extends TestCase
{
    private function arInvoice(string $status): ARInvoice
    {
        return ARInvoice::factory()->create([
            'contact_id' => Contact::factory()->customer()->create()->id,
            'invoice_date' => Carbon::now()->subDays(60),
            'due_date' => Carbon::now()->subDays(30),
            'total_amount' => 1000.00,
            'paid_amount' => 0.00,
            'status' => $status,
            'is_active' => true,
        ]);
    }

    private function apInvoice(string $status): APInvoice
    {
        return APInvoice::factory()->create([
            'contact_id' => Contact::factory()->supplier()->create()->id,
            'invoice_date' => Carbon::now()->subDays(60),
            'due_date' => Carbon::now()->subDays(30),
            'total_amount' => 1000.00,
            'paid_amount' => 0.00,
            'status' => $status,
            'is_active' => true,
        ]);
    }

    public function test_marks_live_invoices_as_overdue(): void
    {
        $ar = $this->arInvoice('posted');
        $ap = $this->apInvoice('posted');

        $this->artisan('finance:check-overdue')->assertExitCode(0);

        $this->assertEquals('overdue', $ar->fresh()->status);
        $this->assertEquals('overdue', $ap->fresh()->status);
    }

    public function test_does_not_revive_voided_or_draft_invoices(): void
    {
        foreach (['void', 'voided', 'cancelled', 'draft', 'paid'] as $status) {
            $ar[$status] = $this->arInvoice($status);
            $ap[$status] = $this->apInvoice($status);
        }

        $this->artisan('finance:check-overdue')->assertExitCode(0);

        foreach ($ar as $status => $invoice) {
            $this->assertEquals($status, $invoice->fresh()->status, "AR {$status} cambio de estado");
            $this->assertEquals($status, $ap[$status]->fresh()->status, "AP {$status} cambio de estado");
        }
    }
}
