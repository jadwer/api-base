<?php

namespace Modules\Accounting\Tests\Feature;

use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalLine;
use Tests\TestCase;

/**
 * Auditoria finanzas 2026-10, punto 7: una poliza manual nacia "posted" por
 * POST sin cuadre, periodo ni folio, y una linea podia mudarse por PATCH a
 * una poliza ya contabilizada. Ahora se crea en borrador y se contabiliza
 * por la accion journal-entries/{id}/post.
 */
class JournalEntryPostTest extends TestCase
{
    private FiscalPeriod $period;
    private Journal $journal;

    protected function setUp(): void
    {
        parent::setUp();
        $this->period = FiscalPeriod::factory()->create([
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'status' => 'open',
        ]);
        $this->journal = Journal::factory()->create();
    }

    private function entry(array $overrides = []): JournalEntry
    {
        return JournalEntry::create(array_merge([
            'journal_id' => $this->journal->id,
            'fiscal_period_id' => $this->period->id,
            'date' => '2026-10-15',
            'description' => 'Poliza manual de prueba',
            'status' => 'draft',
        ], $overrides));
    }

    private function line(JournalEntry $entry, float $debit, float $credit): JournalLine
    {
        $account = Account::factory()->create(['is_postable' => true, 'status' => 'active']);

        return JournalLine::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $account->id,
            'debit' => $debit,
            'credit' => $credit,
        ]);
    }

    private function postEntry(JournalEntry $entry, $user = null)
    {
        return $this->actingAs($user ?? $this->getAdminUser(), 'sanctum')
            ->postJson("/api/v1/journal-entries/{$entry->id}/post");
    }

    public function test_cannot_create_entry_already_posted(): void
    {
        $before = JournalEntry::count();

        $response = $this->actingAs($this->getAdminUser(), 'sanctum')
            ->jsonApi()
            ->expects('journal-entries')
            ->withData([
                'type' => 'journal-entries',
                'attributes' => [
                    'journalId' => $this->journal->id,
                    'fiscalPeriodId' => $this->period->id,
                    'date' => '2026-10-15',
                    'status' => 'posted',
                ],
            ])
            ->post('/api/v1/journal-entries');

        $response->assertStatus(422);
        $this->assertStringContainsString('borrador', json_encode($response->json(), JSON_UNESCAPED_UNICODE));
        $this->assertSame($before, JournalEntry::count());
    }

    public function test_balanced_draft_is_posted_with_number(): void
    {
        $entry = $this->entry();
        $this->line($entry, 400, 0);
        $this->line($entry, 0, 400);

        $this->postEntry($entry)
            ->assertOk()
            ->assertJsonPath('data.status', 'posted');

        $entry->refresh();
        $this->assertSame('posted', $entry->status);
        $this->assertNotNull($entry->number);
        $this->assertNotNull($entry->posted_at);
        $this->assertEquals(400.0, (float) $entry->total_debit);
    }

    public function test_unbalanced_entry_is_rejected(): void
    {
        $entry = $this->entry();
        $this->line($entry, 400, 0);
        $this->line($entry, 0, 300);

        $this->postEntry($entry)->assertStatus(422)->assertJsonPath('error', fn ($e) => str_contains($e, 'no cuadra'));
        $this->assertSame('draft', $entry->fresh()->status);
    }

    public function test_empty_entry_is_rejected(): void
    {
        $entry = $this->entry();

        $this->postEntry($entry)->assertStatus(422);
        $this->assertSame('draft', $entry->fresh()->status);
    }

    public function test_date_outside_period_is_rejected(): void
    {
        $entry = $this->entry(['date' => '2026-11-03']);
        $this->line($entry, 100, 0);
        $this->line($entry, 0, 100);

        $this->postEntry($entry)->assertStatus(422)->assertJsonPath('error', fn ($e) => str_contains($e, 'fuera del periodo'));
        $this->assertSame('draft', $entry->fresh()->status);
    }

    public function test_closed_period_is_rejected(): void
    {
        $this->period->update(['status' => 'closed']);
        $entry = $this->entry();
        $this->line($entry, 100, 0);
        $this->line($entry, 0, 100);

        $this->postEntry($entry)->assertStatus(422);
        $this->assertSame('draft', $entry->fresh()->status);
    }

    public function test_already_posted_entry_cannot_be_posted_again(): void
    {
        $entry = $this->entry(['status' => 'posted', 'number' => 'GL-X-1']);

        $this->postEntry($entry)->assertStatus(422);
    }

    public function test_user_without_permission_cannot_post(): void
    {
        $entry = $this->entry();
        $this->line($entry, 100, 0);
        $this->line($entry, 0, 100);

        $this->postEntry($entry, $this->getCustomerUser())->assertStatus(403);
        $this->assertSame('draft', $entry->fresh()->status);
    }

    public function test_line_cannot_move_into_posted_entry(): void
    {
        $draft = $this->entry();
        $line = $this->line($draft, 50, 0);
        $posted = $this->entry(['status' => 'posted', 'number' => 'GL-X-2']);

        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->jsonApi()
            ->expects('journal-lines')
            ->withData([
                'type' => 'journal-lines',
                'id' => (string) $line->id,
                'attributes' => ['journalEntryId' => $posted->id],
            ])
            ->patch("/api/v1/journal-lines/{$line->id}");

        $this->assertSame($draft->id, $line->fresh()->journal_entry_id);
    }
}
