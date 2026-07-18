<?php

namespace Modules\Accounting\Tests\Integration;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Services\AccountingService;
use Tests\TestCase;

/**
 * INVARIANTE: todo asiento posteado por el camino real (AccountingService)
 * cumple SUM(debit) == SUM(credit) en journal_lines, y un intento descuadrado
 * no deja rastro en la base (ni asiento ni lineas huerfanas).
 *
 * Sin Event::fake(). Se ejercita el servicio real de posteo (el mismo que usan
 * Finance e Inventory via createJournalEntry/postJournalEntry) y se asevera
 * contra la base de datos.
 *
 * NOTA (hallazgo documentado en el reporte de esta tanda): la inmutabilidad
 * TOTAL de un asiento posted via PATCH JSON:API NO existe en el codigo
 * (status/date/description y journal_lines.debit/credit son escribibles).
 * Aqui solo se testea la parte que SI existe: totales protegidos en el Schema
 * (totalDebit/totalCredit readOnly) e idempotencia del re-posteo en el servicio.
 */
class JournalEntryBalanceInvariantTest extends TestCase
{
    private AccountingService $accounting;

    protected function setUp(): void
    {
        parent::setUp();
        $this->accounting = app(AccountingService::class);
    }

    /**
     * Garantiza el diario GL activo (mismo patron que
     * InventoryMovementGLIntegrationTest, el camino real del ciclo).
     */
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
     * Periodo abierto en un mes lejano (2032) para no chocar con los
     * periodos sembrados (2020-2021 del factory y 2025 del seeder).
     */
    private function openPeriodFor(int $month): FiscalPeriod
    {
        return FiscalPeriod::factory()
            ->forDate(\Carbon\Carbon::create(2032, $month, 1))
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

    public function test_balanced_entry_posted_via_real_service_is_posted_and_balanced_in_db(): void
    {
        $this->ensureGlJournal();
        $this->openPeriodFor(3);

        $debitAccount = $this->postableAccount('asset', 'debit');
        $creditAccount = $this->postableAccount('revenue', 'credit');

        $entry = $this->accounting->createJournalEntry(
            'GL',
            '2032-03-15',
            'Invariante balance: asiento valido',
            'INV-BAL-OK-1',
            [
                ['account_id' => $debitAccount->id, 'debit_amount' => 750.50, 'credit_amount' => 0],
                ['account_id' => $creditAccount->id, 'debit_amount' => 0, 'credit_amount' => 750.50],
            ]
        );

        // Estado en la BASE, no en el objeto en memoria
        $row = DB::table('journal_entries')->where('id', $entry->id)->first();
        $this->assertNotNull($row);
        $this->assertSame(JournalEntry::STATUS_POSTED, $row->status);
        $this->assertNotNull($row->posted_at, 'Un asiento posted debe tener posted_at');
        $this->assertNotNull($row->number, 'El posteo real debe asignar folio via SequenceService');

        // INVARIANTE: SUM(debit) == SUM(credit) sumando en journal_lines
        $sumDebit = (float) DB::table('journal_lines')->where('journal_entry_id', $entry->id)->sum('debit');
        $sumCredit = (float) DB::table('journal_lines')->where('journal_entry_id', $entry->id)->sum('credit');

        $this->assertEqualsWithDelta(750.50, $sumDebit, 0.001);
        $this->assertEqualsWithDelta(750.50, $sumCredit, 0.001);
        $this->assertEqualsWithDelta($sumDebit, $sumCredit, 0.001);

        // Los totales cacheados del asiento coinciden con las lineas reales
        $this->assertEqualsWithDelta($sumDebit, (float) $row->total_debit, 0.001);
        $this->assertEqualsWithDelta($sumCredit, (float) $row->total_credit, 0.001);

        $this->assertSame(2, DB::table('journal_lines')->where('journal_entry_id', $entry->id)->count());
    }

    public function test_unbalanced_entry_is_rejected_and_leaves_no_orphan_rows(): void
    {
        $this->ensureGlJournal();
        $this->openPeriodFor(4);

        $debitAccount = $this->postableAccount('asset', 'debit');
        $creditAccount = $this->postableAccount('revenue', 'credit');

        $entriesBefore = DB::table('journal_entries')->count();
        $linesBefore = DB::table('journal_lines')->count();

        $thrown = null;
        try {
            $this->accounting->createJournalEntry(
                'GL',
                '2032-04-15',
                'Invariante balance: asiento descuadrado',
                'INV-BAL-BAD-1',
                [
                    ['account_id' => $debitAccount->id, 'debit_amount' => 500.00, 'credit_amount' => 0],
                    ['account_id' => $creditAccount->id, 'debit_amount' => 0, 'credit_amount' => 300.00],
                ]
            );
        } catch (Exception $e) {
            $thrown = $e;
        }

        $this->assertNotNull($thrown, 'El servicio debe rechazar un asiento descuadrado');
        $this->assertStringContainsString('not balanced', $thrown->getMessage());

        // NO quedo posted ni draft: la transaccion revirtio todo
        $this->assertDatabaseMissing('journal_entries', ['reference' => 'INV-BAL-BAD-1']);
        $this->assertSame($entriesBefore, DB::table('journal_entries')->count(), 'No debe quedar asiento fantasma');
        $this->assertSame($linesBefore, DB::table('journal_lines')->count(), 'No deben quedar lineas huerfanas');

        // Cinturon y tirantes: cero lineas huerfanas globales (sin asiento padre)
        $orphans = DB::table('journal_lines')
            ->leftJoin('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->whereNull('journal_entries.id')
            ->count();
        $this->assertSame(0, $orphans);
    }

    public function test_reposting_a_posted_entry_is_idempotent(): void
    {
        $this->ensureGlJournal();
        $this->openPeriodFor(5);

        $debitAccount = $this->postableAccount('asset', 'debit');
        $creditAccount = $this->postableAccount('revenue', 'credit');

        $entry = $this->accounting->createJournalEntry(
            'GL',
            '2032-05-10',
            'Invariante balance: idempotencia de posteo',
            'INV-BAL-IDEM-1',
            [
                ['account_id' => $debitAccount->id, 'debit_amount' => 200.00, 'credit_amount' => 0],
                ['account_id' => $creditAccount->id, 'debit_amount' => 0, 'credit_amount' => 200.00],
            ]
        );

        $numberBefore = DB::table('journal_entries')->where('id', $entry->id)->value('number');
        $linesBefore = DB::table('journal_lines')->where('journal_entry_id', $entry->id)->count();
        $postedAtBefore = DB::table('journal_entries')->where('id', $entry->id)->value('posted_at');

        // Re-postear por el camino publico del servicio: debe ser no-op
        $result = $this->accounting->postJournalEntry($entry->fresh());
        $this->assertTrue($result);

        $rowAfter = DB::table('journal_entries')->where('id', $entry->id)->first();
        $this->assertSame(JournalEntry::STATUS_POSTED, $rowAfter->status);
        $this->assertSame($numberBefore, $rowAfter->number, 'Re-postear no debe reasignar folio');
        $this->assertEquals($postedAtBefore, $rowAfter->posted_at, 'Re-postear no debe cambiar posted_at');
        $this->assertSame(
            $linesBefore,
            DB::table('journal_lines')->where('journal_entry_id', $entry->id)->count(),
            'Re-postear no debe duplicar lineas'
        );
    }

    public function test_posted_entry_totals_cannot_be_changed_via_public_patch(): void
    {
        $this->ensureGlJournal();
        $this->openPeriodFor(6);

        $debitAccount = $this->postableAccount('asset', 'debit');
        $creditAccount = $this->postableAccount('revenue', 'credit');

        $entry = $this->accounting->createJournalEntry(
            'GL',
            '2032-06-10',
            'Invariante balance: totales readonly via PATCH',
            'INV-BAL-RO-1',
            [
                ['account_id' => $debitAccount->id, 'debit_amount' => 1000.00, 'credit_amount' => 0],
                ['account_id' => $creditAccount->id, 'debit_amount' => 0, 'credit_amount' => 1000.00],
            ]
        );

        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('journal-entries')
            ->withData([
                'type' => 'journal-entries',
                'id' => (string) $entry->id,
                'attributes' => [
                    'totalDebit' => 1.00,
                    'totalCredit' => 999999.00,
                ],
            ])
            ->patch("/api/v1/journal-entries/{$entry->id}");

        // Fase 2.7: un asiento posteado es INMUTABLE por el camino publico
        // (JournalEntryAuthorizer deniega update con 403). Antes de ese guard,
        // los campos readOnly solo se ignoraban en el fill (200); ahora el
        // intento completo se rechaza. El invariante de fondo sigue siendo el
        // mismo: la base no cambia.
        $response->assertStatus(403);

        // INVARIANTE en la base: los totales del asiento posted no cambiaron
        $row = DB::table('journal_entries')->where('id', $entry->id)->first();
        $this->assertEqualsWithDelta(1000.00, (float) $row->total_debit, 0.001);
        $this->assertEqualsWithDelta(1000.00, (float) $row->total_credit, 0.001);
        $this->assertSame(JournalEntry::STATUS_POSTED, $row->status);
    }
}
