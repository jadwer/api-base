<?php

namespace Modules\Accounting\Tests\Feature;

use Modules\Accounting\Models\FiscalPeriod;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Models\JournalEntry;
use Tests\TestCase;

/**
 * El formulario de poliza manual manda solo fecha, descripcion, referencia y
 * moneda; el backend exigia diario y periodo y respondia 422, asi que nunca
 * se pudo capturar una poliza desde la UI (auditoria 2026-10). El diario por
 * omision es GL y el periodo es el abierto que contiene la fecha.
 */
class JournalEntryManualCreateTest extends TestCase
{
    private function postForm(array $attributes)
    {
        return $this->actingAs($this->getAdminUser(), 'sanctum')
            ->jsonApi()
            ->expects('journal-entries')
            ->withData(['type' => 'journal-entries', 'attributes' => $attributes])
            ->post('/api/v1/journal-entries');
    }

    public function test_form_payload_creates_draft_in_gl_and_open_period(): void
    {
        $gl = Journal::firstOrCreate(['code' => 'GL'], ['name' => 'General', 'status' => 'active']);
        $period = FiscalPeriod::factory()->create([
            'start_date' => '2031-03-01',
            'end_date' => '2031-03-31',
            'status' => 'open',
        ]);

        $response = $this->postForm([
            'date' => '2031-03-10',
            'description' => 'Ajuste manual',
            'reference' => 'REF-1',
        ]);

        $response->assertCreated();
        $entry = JournalEntry::findOrFail($response->json('data.id'));
        $this->assertSame($gl->id, $entry->journal_id);
        $this->assertSame($period->id, $entry->fiscal_period_id);
        $this->assertSame('draft', $entry->status);
    }

    public function test_date_without_open_period_is_rejected_in_spanish(): void
    {
        Journal::firstOrCreate(['code' => 'GL'], ['name' => 'General', 'status' => 'active']);

        $response = $this->postForm([
            'date' => '2040-01-10',
            'description' => 'Sin periodo',
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('periodo fiscal abierto', json_encode($response->json(), JSON_UNESCAPED_UNICODE));
    }
}
