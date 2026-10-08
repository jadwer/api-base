<?php

namespace Modules\HR\Tests\Feature;

use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Models\JournalEntry;
use Modules\HR\Models\PayrollItem;
use Modules\HR\Models\PayrollPeriod;
use Tests\TestCase;

/**
 * markAsPaid registra el asiento de nomina en contabilidad. Antes usaba
 * JournalEntryLine (no existe) y columnas inexistentes de journal_entries,
 * asi que el endpoint respondia 422 siempre.
 */
class PayrollPeriodMarkAsPaidTest extends TestCase
{
    private function setUpLedger(bool $withLiabilities = true, bool $openPeriod = true): void
    {
        Journal::firstOrCreate(
            ['code' => 'GL'],
            ['name' => 'General Ledger', 'prefix' => 'GL', 'type' => 'general', 'status' => 'active', 'metadata' => []]
        );

        $now = now();
        if ($openPeriod) {
            FiscalPeriod::firstOrCreate(
                ['year' => $now->year, 'month' => $now->month],
                [
                    'name' => $now->format('Y-m'),
                    'start_date' => $now->copy()->startOfMonth()->format('Y-m-d'),
                    'end_date' => $now->copy()->endOfMonth()->format('Y-m-d'),
                    'status' => 'open',
                    'metadata' => [],
                ]
            );
        }

        $accounts = [
            ['6190', 'Gasto de nómina', 'expense', 'debit'],
            ['1190', 'Banco nómina', 'asset', 'debit'],
        ];
        if ($withLiabilities) {
            $accounts[] = ['2190', 'Pasivo retenciones de nómina', 'liability', 'credit'];
        }

        foreach ($accounts as [$code, $name, $type, $nature]) {
            Account::firstOrCreate(['code' => $code], [
                'name' => $name,
                'account_type' => $type,
                'nature' => $nature,
                'level' => 2,
                'currency' => 'MXN',
                'is_postable' => true,
                'status' => 'active',
            ]);
        }
    }

    private function makePeriod(float $gross, float $deductions): PayrollPeriod
    {
        $period = PayrollPeriod::factory()->create([
            'status' => 'processing',
            'payment_date' => now()->toDateString(),
            'total_gross' => $gross,
            'total_deductions' => $deductions,
        ]);
        PayrollItem::factory()->create(['payroll_period_id' => $period->id, 'status' => 'pending']);

        return $period;
    }

    public function test_admin_marks_period_as_paid_and_posts_journal_entry(): void
    {
        $this->setUpLedger();
        $admin = $this->getAdminUser();
        $period = $this->makePeriod(10000.00, 1500.00);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/payroll-periods/{$period->id}/mark-as-paid");

        $response->assertOk();
        $this->assertSame('paid', $period->fresh()->status);
        $this->assertDatabaseMissing('payroll_items', ['payroll_period_id' => $period->id, 'status' => 'pending']);

        $entry = JournalEntry::with('journalLines.account')->findOrFail($response->json('data.journal_entry_id'));
        $this->assertSame('PAYROLL-' . $period->id, $entry->reference);
        $this->assertSame(JournalEntry::STATUS_POSTED, $entry->status);
        $this->assertSame('GL', $entry->journal->code);
        $this->assertSame(now()->toDateString(), $entry->date->toDateString());
        $this->assertSame($admin->id, $entry->posted_by_id);
        $this->assertSame(PayrollPeriod::class, $entry->source_type);
        $this->assertSame($period->id, $entry->source_id);
        $this->assertNotNull($entry->number);

        $lines = $entry->journalLines;
        $this->assertCount(3, $lines);
        $this->assertEqualsWithDelta(10000.00, $lines->sum('debit'), 0.001);
        $this->assertEqualsWithDelta(10000.00, $lines->sum('credit'), 0.001);
        $this->assertEqualsWithDelta(10000.00, $entry->total_debit, 0.001);

        $byPrefix = fn (string $p) => $lines->first(fn ($l) => str_starts_with($l->account->code, $p));
        $this->assertEqualsWithDelta(10000.00, $byPrefix('6')->debit, 0.001);
        $this->assertEqualsWithDelta(8500.00, $byPrefix('1')->credit, 0.001);
        $this->assertEqualsWithDelta(1500.00, $byPrefix('2')->credit, 0.001);
    }

    public function test_period_without_deductions_posts_two_lines(): void
    {
        $this->setUpLedger(false);
        $period = $this->makePeriod(5000.00, 0);

        $response = $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson("/api/v1/payroll-periods/{$period->id}/mark-as-paid");

        $response->assertOk();
        $entry = JournalEntry::findOrFail($response->json('data.journal_entry_id'));
        $this->assertCount(2, $entry->journalLines);
        $this->assertEqualsWithDelta(5000.00, $entry->total_credit, 0.001);
    }

    public function test_draft_period_cannot_be_marked_as_paid(): void
    {
        $this->setUpLedger();
        $period = PayrollPeriod::factory()->create(['status' => 'draft']);

        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson("/api/v1/payroll-periods/{$period->id}/mark-as-paid")
            ->assertStatus(422);

        $this->assertSame('draft', $period->fresh()->status);
        $this->assertDatabaseMissing('journal_entries', ['reference' => 'PAYROLL-' . $period->id]);
    }

    public function test_failed_posting_rolls_back_period_status(): void
    {
        // Sin periodo fiscal abierto el asiento falla y el periodo no queda pagado
        $period = $this->makePeriod(10000.00, 1500.00);
        FiscalPeriod::query()->update(['status' => 'closed']);
        $this->setUpLedger(openPeriod: false);

        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson("/api/v1/payroll-periods/{$period->id}/mark-as-paid")
            ->assertStatus(422);

        $this->assertSame('processing', $period->fresh()->status);
        $this->assertDatabaseMissing('journal_entries', ['reference' => 'PAYROLL-' . $period->id]);
    }

    public function test_guest_cannot_mark_as_paid(): void
    {
        $period = PayrollPeriod::factory()->create(['status' => 'processing']);

        $this->postJson("/api/v1/payroll-periods/{$period->id}/mark-as-paid")->assertUnauthorized();
    }
}
