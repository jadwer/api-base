<?php

namespace Modules\Accounting\Tests\Feature;

use Tests\TestCase;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\Journal;
use Modules\Finance\Models\ARInvoice;
use Modules\Finance\Models\APInvoice;
use Modules\Finance\Models\BankTransaction;
use Modules\Finance\Models\BankAccount;
use Modules\Contacts\Models\Contact;

/**
 * AC-M001: Tests for Period Close Checklist functionality.
 */
class PeriodCloseChecklistTest extends TestCase
{
    protected function createFiscalPeriod(array $attributes = []): FiscalPeriod
    {
        return FiscalPeriod::factory()->create(array_merge([
            'name' => 'Test Period ' . now()->format('Y-m'),
            'year' => now()->year,
            'month' => now()->month,
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'status' => 'open',
        ], $attributes));
    }

    public function test_admin_can_get_close_checklist(): void
    {
        $user = $this->getAdminUser();
        $period = $this->createFiscalPeriod();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/fiscal-periods/' . $period->id . '/close-checklist');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'period_id',
                'period_name',
                'status',
                'can_close',
                'all_passed',
                'total_checks',
                'passed_checks',
                'warnings_count',
                'errors_count',
                'checks',
            ],
        ]);
    }

    public function test_checklist_detects_unbalanced_journal_entries(): void
    {
        $user = $this->getAdminUser();
        $period = $this->createFiscalPeriod();
        $journal = Journal::factory()->create();

        // Create an unbalanced entry
        JournalEntry::factory()->create([
            'fiscal_period_id' => $period->id,
            'journal_id' => $journal->id,
            'total_debit' => 1000.00,
            'total_credit' => 900.00, // Unbalanced!
            'status' => 'posted',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/fiscal-periods/' . $period->id . '/close-checklist');

        $response->assertOk();

        $data = $response->json('data');
        $this->assertFalse($data['all_passed']);

        // Find the balance check
        $balanceCheck = collect($data['checks'])->firstWhere('id', 'journal_entries_balanced');
        $this->assertFalse($balanceCheck['passed']);
        $this->assertEquals('error', $balanceCheck['severity']);
    }

    public function test_checklist_detects_unposted_entries(): void
    {
        $user = $this->getAdminUser();
        $period = $this->createFiscalPeriod();
        $journal = Journal::factory()->create();

        // Create a draft entry
        JournalEntry::factory()->create([
            'fiscal_period_id' => $period->id,
            'journal_id' => $journal->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/fiscal-periods/' . $period->id . '/close-checklist');

        $response->assertOk();

        $data = $response->json('data');
        $postedCheck = collect($data['checks'])->firstWhere('id', 'all_entries_posted');
        $this->assertFalse($postedCheck['passed']);
    }

    public function test_checklist_warns_about_pending_ar_invoices(): void
    {
        $user = $this->getAdminUser();
        $period = $this->createFiscalPeriod();
        $customer = Contact::factory()->customer()->create();

        // Create pending AR invoice
        ARInvoice::factory()->create([
            'contact_id' => $customer->id,
            'invoice_date' => $period->start_date->addDays(5),
            'status' => 'sent', // Not paid
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/fiscal-periods/' . $period->id . '/close-checklist');

        $response->assertOk();

        $data = $response->json('data');
        $arCheck = collect($data['checks'])->firstWhere('id', 'ar_invoices_reconciled');
        $this->assertFalse($arCheck['passed']);
        $this->assertEquals('warning', $arCheck['severity']);
    }

    public function test_checklist_warns_about_pending_ap_invoices(): void
    {
        $user = $this->getAdminUser();
        $period = $this->createFiscalPeriod();
        $vendor = Contact::factory()->supplier()->create();

        // Create pending AP invoice
        APInvoice::factory()->create([
            'contact_id' => $vendor->id,
            'invoice_date' => $period->start_date->addDays(5),
            'status' => 'pending', // Not paid
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/fiscal-periods/' . $period->id . '/close-checklist');

        $response->assertOk();

        $data = $response->json('data');
        $apCheck = collect($data['checks'])->firstWhere('id', 'ap_invoices_reconciled');
        $this->assertFalse($apCheck['passed']);
        $this->assertEquals('warning', $apCheck['severity']);
    }

    public function test_checklist_warns_about_unreconciled_bank_transactions(): void
    {
        $user = $this->getAdminUser();
        $period = $this->createFiscalPeriod();
        $bankAccount = BankAccount::factory()->create();

        // Create unreconciled transaction
        BankTransaction::factory()->create([
            'bank_account_id' => $bankAccount->id,
            'transaction_date' => $period->start_date->addDays(5),
            'reconciled_at' => null,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/fiscal-periods/' . $period->id . '/close-checklist');

        $response->assertOk();

        $data = $response->json('data');
        $bankCheck = collect($data['checks'])->firstWhere('id', 'bank_reconciliation_complete');
        $this->assertFalse($bankCheck['passed']);
    }

    public function test_checklist_passes_when_all_conditions_met(): void
    {
        $user = $this->getAdminUser();
        $period = $this->createFiscalPeriod();
        $journal = Journal::factory()->create();

        // Create balanced, posted entry
        JournalEntry::factory()->create([
            'fiscal_period_id' => $period->id,
            'journal_id' => $journal->id,
            'total_debit' => 1000.00,
            'total_credit' => 1000.00,
            'status' => 'posted',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/fiscal-periods/' . $period->id . '/close-checklist');

        $response->assertOk();

        $data = $response->json('data');
        // Check for critical checks (errors)
        $this->assertEquals(0, $data['errors_count']);
    }

    public function test_admin_can_close_period(): void
    {
        $user = $this->getAdminUser();
        $period = $this->createFiscalPeriod();
        $journal = Journal::factory()->create();

        // Create balanced, posted entry
        JournalEntry::factory()->create([
            'fiscal_period_id' => $period->id,
            'journal_id' => $journal->id,
            'total_debit' => 1000.00,
            'total_credit' => 1000.00,
            'status' => 'posted',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/fiscal-periods/' . $period->id . '/close');

        $response->assertOk();

        $data = $response->json('data');
        $this->assertTrue($data['success']);
        $this->assertEquals('Período cerrado exitosamente', $data['message']);

        // Verify period is closed
        $period->refresh();
        $this->assertEquals('closed', $period->status);
        $this->assertNotNull($period->closed_at);
        $this->assertEquals($user->id, $period->closed_by_id);
    }

    public function test_cannot_close_period_with_errors(): void
    {
        $user = $this->getAdminUser();
        $period = $this->createFiscalPeriod();
        $journal = Journal::factory()->create();

        // Create unbalanced entry (error condition)
        JournalEntry::factory()->create([
            'fiscal_period_id' => $period->id,
            'journal_id' => $journal->id,
            'total_debit' => 1000.00,
            'total_credit' => 500.00,
            'status' => 'posted',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/fiscal-periods/' . $period->id . '/close');

        $response->assertStatus(422);

        $data = $response->json('data');
        $this->assertFalse($data['success']);

        // Period should still be open
        $period->refresh();
        $this->assertEquals('open', $period->status);
    }

    public function test_can_force_close_period_with_warnings(): void
    {
        $user = $this->getAdminUser();
        $period = $this->createFiscalPeriod();
        $customer = Contact::factory()->customer()->create();

        // Create pending AR invoice (warning, not error)
        ARInvoice::factory()->create([
            'contact_id' => $customer->id,
            'invoice_date' => $period->start_date->addDays(5),
            'status' => 'sent',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/fiscal-periods/' . $period->id . '/close', [
                'force' => true,
            ]);

        $response->assertOk();

        $data = $response->json('data');
        $this->assertTrue($data['success']);

        $period->refresh();
        $this->assertEquals('closed', $period->status);
    }

    public function test_cannot_close_already_closed_period(): void
    {
        $user = $this->getAdminUser();
        $period = $this->createFiscalPeriod(['status' => 'closed']);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/fiscal-periods/' . $period->id . '/close');

        $response->assertStatus(422);

        $data = $response->json('data');
        $this->assertFalse($data['success']);
        $this->assertEquals('El período ya está cerrado', $data['message']);
    }

    public function test_admin_can_reopen_closed_period(): void
    {
        $user = $this->getAdminUser();
        $period = $this->createFiscalPeriod([
            'status' => 'closed',
            'closed_at' => now(),
            'closed_by_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/fiscal-periods/' . $period->id . '/reopen', [
                'reason' => 'Corrección de asientos',
            ]);

        $response->assertOk();

        $data = $response->json('data');
        $this->assertTrue($data['success']);
        $this->assertEquals('Período reabierto exitosamente', $data['message']);

        // Verify period is open
        $period->refresh();
        $this->assertEquals('open', $period->status);
        $this->assertNull($period->closed_at);
    }

    public function test_cannot_reopen_already_open_period(): void
    {
        $user = $this->getAdminUser();
        $period = $this->createFiscalPeriod(['status' => 'open']);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/fiscal-periods/' . $period->id . '/reopen');

        $response->assertStatus(422);

        $data = $response->json('data');
        $this->assertFalse($data['success']);
        $this->assertEquals('El período no está cerrado', $data['message']);
    }

    public function test_admin_can_get_period_summary(): void
    {
        $user = $this->getAdminUser();
        $period = $this->createFiscalPeriod();
        $journal = Journal::factory()->create();

        // Create some entries
        JournalEntry::factory()->create([
            'fiscal_period_id' => $period->id,
            'journal_id' => $journal->id,
            'total_debit' => 5000.00,
            'total_credit' => 5000.00,
            'status' => 'posted',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/fiscal-periods/' . $period->id . '/summary');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'period' => [
                    'id',
                    'name',
                    'year',
                    'month',
                    'start_date',
                    'end_date',
                    'status',
                ],
                'journal_entries' => [
                    'total_count',
                    'total_debits',
                    'total_credits',
                    'is_balanced',
                ],
                'accounts_receivable' => [
                    'total_invoices',
                    'total_amount',
                    'paid_count',
                    'pending_count',
                ],
                'accounts_payable' => [
                    'total_invoices',
                    'total_amount',
                    'paid_count',
                    'pending_count',
                ],
            ],
        ]);

        $data = $response->json('data');
        $this->assertEquals(1, $data['journal_entries']['total_count']);
        $this->assertEquals(5000.00, $data['journal_entries']['total_debits']);
        $this->assertTrue($data['journal_entries']['is_balanced']);
    }

    public function test_guest_cannot_access_close_checklist(): void
    {
        $period = $this->createFiscalPeriod();

        $response = $this->getJson('/api/v1/fiscal-periods/' . $period->id . '/close-checklist');

        $response->assertStatus(401);
    }

    public function test_guest_cannot_close_period(): void
    {
        $period = $this->createFiscalPeriod();

        $response = $this->postJson('/api/v1/fiscal-periods/' . $period->id . '/close');

        $response->assertStatus(401);
    }

    public function test_reopen_history_is_recorded(): void
    {
        $user = $this->getAdminUser();
        $period = $this->createFiscalPeriod([
            'status' => 'closed',
            'closed_at' => now()->subDays(5),
            'closed_by_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/fiscal-periods/' . $period->id . '/reopen', [
                'reason' => 'Ajuste pendiente',
            ]);

        $response->assertOk();

        $period->refresh();
        $this->assertArrayHasKey('reopen_history', $period->metadata);
        $this->assertCount(1, $period->metadata['reopen_history']);
        $this->assertEquals('Ajuste pendiente', $period->metadata['reopen_history'][0]['reason']);
    }
}
