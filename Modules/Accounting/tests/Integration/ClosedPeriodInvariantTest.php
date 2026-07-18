<?php

namespace Modules\Accounting\Tests\Integration;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalLine;
use Modules\Accounting\Services\AccountingService;
use Tests\TestCase;

/**
 * INVARIANTE: no se puede postear un asiento en un FiscalPeriod cerrado.
 *
 * La regla vive en dos capas reales (verificadas en codigo antes de escribir esto):
 * - AccountingService::createJournalEntry busca SOLO periodos con status 'open'
 *   para la fecha del asiento (lanza si no hay).
 * - AccountingService::validatePeriod (llamada por postJournalEntry) rechaza
 *   postear un draft cuyo periodo asignado ya esta 'closed'.
 *
 * El cierre del periodo se ejecuta por el camino real:
 * POST /api/v1/fiscal-periods/{id}/close -> PeriodCloseService::closePeriod.
 *
 * Sin Event::fake(); asserts contra la base.
 */
class ClosedPeriodInvariantTest extends TestCase
{
    private AccountingService $accounting;

    protected function setUp(): void
    {
        parent::setUp();
        $this->accounting = app(AccountingService::class);
    }

    private function ensureGlJournal(): Journal
    {
        return Journal::firstOrCreate(
            ['code' => 'GL'],
            [
                'name' => 'General Ledger',
                'description' => 'General Ledger Journal',
                'prefix' => 'GL',
                'type' => 'general',
                'status' => 'active',
                'metadata' => [],
            ]
        );
    }

    /**
     * Periodo abierto en 2033 para no chocar con los sembrados
     * (2020-2021 del factory, 2025 del seeder, 2032 del test de balance).
     */
    private function openPeriodFor(int $month): FiscalPeriod
    {
        return FiscalPeriod::factory()
            ->forDate(\Carbon\Carbon::create(2033, $month, 1))
            ->create();
    }

    private function postableAccount(string $type, string $nature): Account
    {
        return Account::factory()->create([
            'account_type' => $type,
            'nature' => $nature,
            'is_postable' => true,
            'status' => 'active',
        ]);
    }

    /**
     * Cierra el periodo por el endpoint real (camino publico de cierre).
     */
    private function closePeriodViaEndpoint(FiscalPeriod $period, bool $force = false): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/fiscal-periods/{$period->id}/close", ['force' => $force]);

        $response->assertOk();
        $this->assertTrue(
            (bool) $response->json('data.success'),
            'El cierre real del periodo debio ser exitoso: ' . json_encode($response->json('data.message'))
        );
    }

    public function test_period_closed_via_real_endpoint_is_closed_in_db(): void
    {
        $period = $this->openPeriodFor(2);

        $this->closePeriodViaEndpoint($period);

        $row = DB::table('fiscal_periods')->where('id', $period->id)->first();
        $this->assertSame('closed', $row->status);
        $this->assertNotNull($row->closed_at);
        $this->assertEquals($this->getAdminUser()->id, $row->closed_by_id);
    }

    public function test_posting_entry_dated_inside_closed_period_is_rejected(): void
    {
        $this->ensureGlJournal();
        $period = $this->openPeriodFor(3);
        $this->closePeriodViaEndpoint($period);

        $debitAccount = $this->postableAccount('asset', 'debit');
        $creditAccount = $this->postableAccount('revenue', 'credit');

        $entriesBefore = DB::table('journal_entries')->count();
        $linesBefore = DB::table('journal_lines')->count();

        $thrown = null;
        try {
            $this->accounting->createJournalEntry(
                'GL',
                '2033-03-15',
                'Invariante periodo: intento en periodo cerrado',
                'INV-PER-CLOSED-1',
                [
                    ['account_id' => $debitAccount->id, 'debit_amount' => 100.00, 'credit_amount' => 0],
                    ['account_id' => $creditAccount->id, 'debit_amount' => 0, 'credit_amount' => 100.00],
                ]
            );
        } catch (Exception $e) {
            $thrown = $e;
        }

        $this->assertNotNull($thrown, 'Postear con fecha dentro de un periodo cerrado debe rechazarse');
        $this->assertStringContainsString('No open fiscal period', $thrown->getMessage());

        // Nada persistido
        $this->assertDatabaseMissing('journal_entries', ['reference' => 'INV-PER-CLOSED-1']);
        $this->assertSame($entriesBefore, DB::table('journal_entries')->count());
        $this->assertSame($linesBefore, DB::table('journal_lines')->count());
    }

    public function test_posting_draft_whose_period_closed_after_creation_is_rejected(): void
    {
        $journal = $this->ensureGlJournal();
        $period = $this->openPeriodFor(4);

        $debitAccount = $this->postableAccount('asset', 'debit');
        $creditAccount = $this->postableAccount('revenue', 'credit');

        // Arrange: draft balanceado ya asignado al periodo (aun abierto)
        $entry = JournalEntry::factory()->draft()->create([
            'journal_id' => $journal->id,
            'fiscal_period_id' => $period->id,
            'date' => '2033-04-10',
            'number' => null,
            'reference' => 'INV-PER-DRAFT-1',
            'total_debit' => 300.00,
            'total_credit' => 300.00,
        ]);
        JournalLine::factory()->create([
            'journal_entry_id' => $entry->id,
            'account_id' => $debitAccount->id,
            'debit' => 300.00,
            'credit' => 0,
        ]);
        JournalLine::factory()->create([
            'journal_entry_id' => $entry->id,
            'account_id' => $creditAccount->id,
            'debit' => 0,
            'credit' => 300.00,
        ]);

        // Cierre real: con draft pendiente el checklist marca error, se fuerza
        // (el endpoint expone 'force' justo para ese caso)
        $this->closePeriodViaEndpoint($period, force: true);

        // Act: intentar postear el draft por el servicio real
        $thrown = null;
        try {
            $this->accounting->postJournalEntry($entry->fresh());
        } catch (Exception $e) {
            $thrown = $e;
        }

        $this->assertNotNull($thrown, 'postJournalEntry debe rechazar un periodo cerrado');
        $this->assertStringContainsString('closed fiscal period', $thrown->getMessage());

        // El asiento sigue draft, sin folio ni posted_at
        $row = DB::table('journal_entries')->where('id', $entry->id)->first();
        $this->assertSame(JournalEntry::STATUS_DRAFT, $row->status);
        $this->assertNull($row->posted_at);
        $this->assertNull($row->number);
    }

    public function test_posting_in_open_period_proceeds(): void
    {
        $this->ensureGlJournal();
        $this->openPeriodFor(5);

        $debitAccount = $this->postableAccount('asset', 'debit');
        $creditAccount = $this->postableAccount('revenue', 'credit');

        $entry = $this->accounting->createJournalEntry(
            'GL',
            '2033-05-15',
            'Invariante periodo: posteo en periodo abierto',
            'INV-PER-OPEN-1',
            [
                ['account_id' => $debitAccount->id, 'debit_amount' => 450.00, 'credit_amount' => 0],
                ['account_id' => $creditAccount->id, 'debit_amount' => 0, 'credit_amount' => 450.00],
            ]
        );

        $row = DB::table('journal_entries')->where('id', $entry->id)->first();
        $this->assertSame(JournalEntry::STATUS_POSTED, $row->status);
        $this->assertNotNull($row->posted_at);

        $sumDebit = (float) DB::table('journal_lines')->where('journal_entry_id', $entry->id)->sum('debit');
        $sumCredit = (float) DB::table('journal_lines')->where('journal_entry_id', $entry->id)->sum('credit');
        $this->assertEqualsWithDelta($sumDebit, $sumCredit, 0.001);
    }
}
