<?php

namespace Modules\Sales\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Sales\Models\FolioSequence;

/**
 * Sync folio sequences against real documents.
 *
 * After importing historical data into a tenant database, folio_sequences
 * (and invoice_series) keep their seeded current_sequence values, which
 * causes the next issued folio to collide with existing documents.
 *
 * This command scans the source tables of each document type, extracts the
 * numeric suffix of every folio that matches the configured format for the
 * sequence (prefix + optional year + padded number joined by separator),
 * and raises current_sequence to the detected maximum. It never decrements.
 *
 * Also syncs invoice_series.current_folio against the maximum folio in
 * cfdi_invoices per (company_setting_id, series code).
 *
 * Safe by default: without --force it runs as a dry-run.
 */
class SyncFoliosCommand extends Command
{
    protected $signature = 'system:sync-folios
                            {--dry-run : Show proposed changes without applying them}
                            {--force : Apply the changes}';

    protected $description = 'Sync folio_sequences (and invoice_series) with the maximum folio found in real documents. Dry-run by default; use --force to apply.';

    /**
     * Map of document_type => [table, column] where issued folios live.
     *
     * Confirmed consumers of FolioSequence::getNextFolio():
     * - quote:          Modules/Sales/app/Models/Quote.php (generateQuoteNumber)
     * - sales_order:    QuoteController::convert, ShoppingCartController::checkout, CheckoutService
     * - purchase_order: Modules/Purchase/app/Models/PurchaseOrder.php (generateOrderNumber)
     * - remission:      Modules/Sales/app/Models/Remission.php (generateRemissionNumber)
     * - fractionation:  Modules/Inventory/app/Services/FractionationService.php
     *
     * The seeded types invoice, invoice_online and invoice_refac have no
     * consumer (CFDI emission takes folios from invoice_series / Billing),
     * so they are intentionally absent and reported as "sin fuente".
     */
    protected const SOURCE_MAP = [
        'quote' => ['quotes', 'quote_number'],
        'sales_order' => ['sales_orders', 'order_number'],
        'purchase_order' => ['purchase_orders', 'order_number'],
        'remission' => ['remissions', 'remission_number'],
        'fractionation' => ['fractionations', 'folio_number'],
    ];

    public function handle(): int
    {
        $apply = (bool) $this->option('force');

        if (!$apply) {
            $this->warn('Modo dry-run (default seguro): no se modificara nada. Usa --force para aplicar.');
        }

        $this->info('Sincronizando folio_sequences...');
        $rows = $this->syncFolioSequences($apply);
        $this->table(['document_type', 'current_sequence', 'max detectado', 'accion'], $rows);

        $this->info('Sincronizando invoice_series (Billing)...');
        $seriesRows = $this->syncInvoiceSeries($apply);

        if ($seriesRows === null) {
            $this->line('Tabla invoice_series no existe en esta base de datos, seccion omitida.');
        } elseif (empty($seriesRows)) {
            $this->line('No hay filas en invoice_series.');
        } else {
            $this->table(['serie (company)', 'current_folio', 'max detectado', 'accion'], $seriesRows);
        }

        if (!$apply) {
            $this->warn('Dry-run: ningun cambio fue aplicado. Ejecuta con --force para aplicar.');
        } else {
            $this->info('Sincronizacion aplicada.');
        }

        return self::SUCCESS;
    }

    /**
     * Sync every folio_sequences row against its source table.
     *
     * @return array<int, array<int, string>> Rows for the output table.
     */
    protected function syncFolioSequences(bool $apply): array
    {
        $rows = [];

        foreach (FolioSequence::orderBy('document_type')->get() as $sequence) {
            $source = self::SOURCE_MAP[$sequence->document_type] ?? null;

            if ($source === null) {
                $rows[] = [$sequence->document_type, (string) $sequence->current_sequence, '-', 'sin fuente, omitido'];
                continue;
            }

            [$table, $column] = $source;

            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
                $rows[] = [$sequence->document_type, (string) $sequence->current_sequence, '-', "sin fuente ({$table}.{$column} no existe), omitido"];
                continue;
            }

            $max = $this->detectMaxSequence($sequence, $table, $column);

            if ($max === null) {
                $rows[] = [$sequence->document_type, (string) $sequence->current_sequence, '0 folios con formato', 'skip'];
                continue;
            }

            if ($max <= $sequence->current_sequence) {
                $rows[] = [$sequence->document_type, (string) $sequence->current_sequence, (string) $max, 'skip (secuencia ya adelantada, nunca se decrementa)'];
                continue;
            }

            if ($apply) {
                $this->applySequenceUpdate($sequence, $max);
                $action = "update ({$sequence->current_sequence} -> {$max})";
            } else {
                $action = "update propuesto ({$sequence->current_sequence} -> {$max})";
            }

            $rows[] = [$sequence->document_type, (string) $sequence->current_sequence, (string) $max, $action];
        }

        return $rows;
    }

    /**
     * Scan the source table and return the maximum numeric suffix among
     * folios matching the sequence format, or null if none matches.
     */
    protected function detectMaxSequence(FolioSequence $sequence, string $table, string $column): ?int
    {
        $pattern = $this->buildFolioPattern($sequence);
        $max = null;

        DB::table($table)
            ->select($column)
            ->whereNotNull($column)
            ->orderBy($column)
            ->chunk(1000, function ($chunk) use (&$max, $pattern, $column) {
                foreach ($chunk as $row) {
                    if (preg_match($pattern, (string) $row->{$column}, $matches)) {
                        $value = (int) $matches[1];
                        if ($max === null || $value > $max) {
                            $max = $value;
                        }
                    }
                }
            });

        return $max;
    }

    /**
     * Build a regex mirroring FolioSequence::formatFolio():
     * non-empty parts (prefix, optional year, padded number) joined by separator.
     *
     * For reset_yearly sequences only the CURRENT year folios count, since
     * current_sequence restarts every year. Otherwise any year is accepted.
     */
    protected function buildFolioPattern(FolioSequence $sequence): string
    {
        $parts = [];

        if (!empty($sequence->prefix)) {
            $parts[] = preg_quote($sequence->prefix, '/');
        }

        if ($sequence->include_year) {
            if ($sequence->reset_yearly) {
                $parts[] = preg_quote(now()->format($sequence->year_format), '/');
            } else {
                $parts[] = $sequence->year_format === 'Y' ? '\d{4}' : '\d{2}';
            }
        }

        // str_pad never truncates, so the number has AT LEAST `padding` digits.
        $padding = max(1, (int) $sequence->padding);
        $parts[] = '(\d{' . $padding . ',})';

        $separator = preg_quote($sequence->separator ?? '', '/');

        return '/^' . implode($separator, $parts) . '$/';
    }

    /**
     * Raise current_sequence atomically. Never decrements: the max is
     * re-checked against the locked row inside the transaction.
     */
    protected function applySequenceUpdate(FolioSequence $sequence, int $max): void
    {
        DB::transaction(function () use ($sequence, $max) {
            $locked = FolioSequence::whereKey($sequence->id)->lockForUpdate()->first();

            if ($locked === null || $max <= $locked->current_sequence) {
                return;
            }

            $locked->current_sequence = $max;

            // Prevent getNextFolio() from resetting the freshly synced value
            // when the imported data belongs to the current year.
            if ($locked->reset_yearly) {
                $locked->last_reset_year = (int) now()->format('Y');
            }

            $locked->save();
        });
    }

    /**
     * Sync invoice_series.current_folio against max(cfdi_invoices.folio)
     * per (company_setting_id, series code). Same increase-only semantics.
     *
     * @return array<int, array<int, string>>|null Null when the table does not exist.
     */
    protected function syncInvoiceSeries(bool $apply): ?array
    {
        if (!Schema::hasTable('invoice_series')) {
            return null;
        }

        $hasSource = Schema::hasTable('cfdi_invoices');
        $rows = [];

        foreach (DB::table('invoice_series')->orderBy('code')->get() as $series) {
            $label = "{$series->code} (company {$series->company_setting_id})";

            if (!$hasSource) {
                $rows[] = [$label, (string) $series->current_folio, '-', 'sin fuente (cfdi_invoices no existe), omitido'];
                continue;
            }

            $query = DB::table('cfdi_invoices')
                ->where('company_setting_id', $series->company_setting_id)
                ->where('series', $series->code);

            // Yearly-reset series only count current year invoices,
            // since current_folio restarts every year.
            if ($series->reset_yearly) {
                $query->whereYear('fecha_emision', now()->format('Y'));
            }

            $max = $query->max('folio');

            if ($max === null) {
                $rows[] = [$label, (string) $series->current_folio, '0 facturas', 'skip'];
                continue;
            }

            $max = (int) $max;

            if ($max <= (int) $series->current_folio) {
                $rows[] = [$label, (string) $series->current_folio, (string) $max, 'skip (folio ya adelantado, nunca se decrementa)'];
                continue;
            }

            if ($apply) {
                $this->applySeriesUpdate((int) $series->id, $max, (bool) $series->reset_yearly);
                $action = "update ({$series->current_folio} -> {$max})";
            } else {
                $action = "update propuesto ({$series->current_folio} -> {$max})";
            }

            $rows[] = [$label, (string) $series->current_folio, (string) $max, $action];
        }

        return $rows;
    }

    /**
     * Raise invoice_series.current_folio atomically, increase-only.
     */
    protected function applySeriesUpdate(int $seriesId, int $max, bool $resetYearly): void
    {
        DB::transaction(function () use ($seriesId, $max, $resetYearly) {
            $locked = DB::table('invoice_series')->where('id', $seriesId)->lockForUpdate()->first();

            if ($locked === null || $max <= (int) $locked->current_folio) {
                return;
            }

            $data = ['current_folio' => $max, 'updated_at' => now()];

            // Prevent checkYearlyReset() from wiping the synced value.
            if ($resetYearly) {
                $data['last_reset_year'] = (int) now()->format('Y');
            }

            DB::table('invoice_series')->where('id', $seriesId)->update($data);
        });
    }
}
