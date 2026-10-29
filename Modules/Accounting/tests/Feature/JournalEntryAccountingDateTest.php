<?php

namespace Modules\Accounting\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalLine;
use Modules\Reports\Services\FinancialStatements\BalanceSheetService;
use Modules\Reports\Services\FinancialStatements\IncomeStatementService;
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

    public function test_balance_sheet_includes_net_income_and_balances(): void
    {
        $bank = Account::factory()->create(['code' => '1192', 'account_type' => 'asset', 'nature' => 'debit', 'status' => 'active']);
        $sales = Account::factory()->create(['code' => '4192', 'account_type' => 'revenue', 'nature' => 'credit', 'status' => 'active']);
        $entry = JournalEntry::factory()->create(['date' => '2026-10-20', 'status' => 'posted', 'total_debit' => 1160, 'total_credit' => 1160]);
        JournalLine::factory()->create(['journal_entry_id' => $entry->id, 'account_id' => $bank->id, 'debit' => 1160, 'credit' => 0]);
        JournalLine::factory()->create(['journal_entry_id' => $entry->id, 'account_id' => $sales->id, 'debit' => 0, 'credit' => 1160]);

        $report = app(BalanceSheetService::class)->generate(Carbon::parse('2026-10-24'));

        // Toda poliza cuadra, asi que el balance debe cuadrar con el resultado
        // del ejercicio dentro del capital.
        $this->assertTrue($report['balanced']);
        $names = collect($report['equity'])->flatMap(fn ($g) => $g['accounts'])->pluck('name');
        $this->assertContains('Resultado del ejercicio', $names);
        $this->assertNotContains('Liability', collect($report['liabilities'])->pluck('category'));
    }

    public function test_income_statement_categories_follow_the_chart_of_accounts(): void
    {
        $bank = Account::factory()->create(['code' => '1193', 'account_type' => 'asset', 'nature' => 'debit', 'status' => 'active']);
        $sales = Account::factory()->create(['code' => '4193', 'account_type' => 'revenue', 'nature' => 'credit', 'status' => 'active']);
        $cost = Account::factory()->create(['code' => '5193', 'account_type' => 'expense', 'nature' => 'debit', 'status' => 'active']);
        $wages = Account::factory()->create(['code' => '6193', 'account_type' => 'expense', 'nature' => 'debit', 'status' => 'active']);
        $entry = JournalEntry::factory()->create(['date' => '2026-10-20', 'status' => 'posted', 'total_debit' => 1000, 'total_credit' => 1000]);
        JournalLine::factory()->create(['journal_entry_id' => $entry->id, 'account_id' => $bank->id, 'debit' => 400, 'credit' => 0]);
        JournalLine::factory()->create(['journal_entry_id' => $entry->id, 'account_id' => $cost->id, 'debit' => 400, 'credit' => 0]);
        JournalLine::factory()->create(['journal_entry_id' => $entry->id, 'account_id' => $wages->id, 'debit' => 200, 'credit' => 0]);
        JournalLine::factory()->create(['journal_entry_id' => $entry->id, 'account_id' => $sales->id, 'debit' => 0, 'credit' => 1000]);

        $report = app(IncomeStatementService::class)->generate(Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'));

        // Antes los prefijos eran de otro catalogo y salia "Revenue"/"Expense".
        $this->assertContains('Ingresos', collect($report['revenues'])->pluck('category'));
        $expenses = collect($report['expenses'])->pluck('category');
        $this->assertContains('Costo de ventas', $expenses);
        $this->assertContains('Gastos de operación', $expenses);
        $this->assertNotContains('Revenue', collect($report['revenues'])->pluck('category'));
        $this->assertNotContains('Expense', $expenses);
    }
}
