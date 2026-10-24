<?php

namespace Modules\Accounting\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalLine;
use Modules\Reports\Services\FinancialStatements\BalanceSheetService;
use Tests\TestCase;

/**
 * Las polizas nacian sin accounting_date y los estados financieros, que
 * filtran por ella, salian en cero (24-10).
 */
class JournalEntryAccountingDateTest extends TestCase
{
    use RefreshDatabase;

    public function test_accounting_date_defaults_to_entry_date(): void
    {
        $entry = JournalEntry::factory()->create(['date' => '2026-10-20', 'status' => 'posted']);

        $this->assertSame('2026-10-20', $entry->fresh()->accounting_date->toDateString());
    }

    public function test_explicit_accounting_date_is_kept(): void
    {
        $entry = JournalEntry::factory()->create(['date' => '2026-10-20', 'accounting_date' => '2026-10-31', 'status' => 'posted']);

        $this->assertSame('2026-10-31', $entry->fresh()->accounting_date->toDateString());
    }

    public function test_balance_sheet_reflects_posted_entries(): void
    {
        $bank = Account::factory()->create(['code' => '1191', 'account_type' => 'asset', 'nature' => 'debit', 'status' => 'active']);
        $capital = Account::factory()->create(['code' => '3191', 'account_type' => 'equity', 'nature' => 'credit', 'status' => 'active']);
        $entry = JournalEntry::factory()->create(['date' => '2026-10-20', 'status' => 'posted', 'total_debit' => 500, 'total_credit' => 500]);
        JournalLine::factory()->create(['journal_entry_id' => $entry->id, 'account_id' => $bank->id, 'debit' => 500, 'credit' => 0]);
        JournalLine::factory()->create(['journal_entry_id' => $entry->id, 'account_id' => $capital->id, 'debit' => 0, 'credit' => 500]);

        $report = app(BalanceSheetService::class)->generate(Carbon::parse('2026-10-24'));

        // La base de pruebas trae otras polizas: se buscan las dos cuentas.
        $balances = collect(json_decode(json_encode([$report['assets'], $report['equity']]), true))
            ->flatten(1)->flatMap(fn ($group) => $group['accounts'] ?? [$group])
            ->pluck('balance', 'code');
        $this->assertEquals(500, $balances['1191'] ?? null);
        $this->assertEquals(500, $balances['3191'] ?? null);
    }
}
