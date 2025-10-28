<?php

namespace Modules\Finance\Console\Commands;

use Illuminate\Console\Command;
use Modules\Finance\Services\EventReplayService;
use Carbon\Carbon;

class ReplayEventsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:replay-events
                            {--flow= : Specific flow to replay (order-to-cash, procure-to-pay, ar-posted, ap-posted, all)}
                            {--since= : Only replay events since this date (Y-m-d format)}
                            {--health : Show health report instead of replaying}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Replay failed event-driven integration events';

    private EventReplayService $eventReplayService;

    public function __construct(EventReplayService $eventReplayService)
    {
        parent::__construct();
        $this->eventReplayService = $eventReplayService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Show health report if requested
        if ($this->option('health')) {
            return $this->showHealthReport();
        }

        $flow = $this->option('flow') ?? 'all';
        $since = $this->option('since') ? Carbon::parse($this->option('since')) : null;

        $this->info('Starting event replay...');
        $this->info('Flow: ' . $flow);
        if ($since) {
            $this->info('Since: ' . $since->toDateString());
        }
        $this->newLine();

        $result = match ($flow) {
            'order-to-cash' => $this->eventReplayService->replayOrderToCash($since),
            'procure-to-pay' => $this->eventReplayService->replayProcureToPay($since),
            'ar-posted' => $this->eventReplayService->replayARInvoicePosted($since),
            'ap-posted' => $this->eventReplayService->replayAPInvoicePosted($since),
            'all' => $this->eventReplayService->replayAll($since),
            default => $this->eventReplayService->replayAll($since),
        };

        // Display results
        if ($flow === 'all') {
            $this->displayAllResults($result);
        } else {
            $this->displayFlowResults($flow, $result);
        }

        return Command::SUCCESS;
    }

    /**
     * Display results for all flows
     */
    private function displayAllResults(array $result): void
    {
        $this->info('=== Event Replay Results ===');
        $this->newLine();

        // Summary table
        $this->table(
            ['Flow', 'Total', 'Processed', 'Failed'],
            [
                ['Order-to-Cash', $result['by_flow']['order_to_cash']['total'], $result['by_flow']['order_to_cash']['processed'], $result['by_flow']['order_to_cash']['failed']],
                ['Procure-to-Pay', $result['by_flow']['procure_to_pay']['total'], $result['by_flow']['procure_to_pay']['processed'], $result['by_flow']['procure_to_pay']['failed']],
                ['AR Invoice Posted', $result['by_flow']['ar_invoice_posted']['total'], $result['by_flow']['ar_invoice_posted']['processed'], $result['by_flow']['ar_invoice_posted']['failed']],
                ['AP Invoice Posted', $result['by_flow']['ap_invoice_posted']['total'], $result['by_flow']['ap_invoice_posted']['processed'], $result['by_flow']['ap_invoice_posted']['failed']],
                ['---', '---', '---', '---'],
                ['TOTAL', $result['total'], $result['processed'], $result['failed']],
            ]
        );

        // Show errors if any
        foreach ($result['by_flow'] as $flowName => $flowResult) {
            if (!empty($flowResult['errors'])) {
                $this->error("Errors in {$flowName}:");
                foreach ($flowResult['errors'] as $error) {
                    $this->line('  - ' . json_encode($error));
                }
                $this->newLine();
            }
        }

        if ($result['failed'] === 0) {
            $this->info('All events replayed successfully!');
        } else {
            $this->warn('Some events failed to replay. Check logs for details.');
        }
    }

    /**
     * Display results for a single flow
     */
    private function displayFlowResults(string $flow, array $result): void
    {
        $this->info("=== {$flow} Replay Results ===");
        $this->newLine();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Found', $result['total']],
                ['Processed', $result['processed']],
                ['Failed', $result['failed']],
            ]
        );

        if (!empty($result['errors'])) {
            $this->error('Errors:');
            foreach ($result['errors'] as $error) {
                $this->line('  - ' . json_encode($error));
            }
        }

        if ($result['failed'] === 0 && $result['total'] > 0) {
            $this->info('All events replayed successfully!');
        } elseif ($result['total'] === 0) {
            $this->info('No events found to replay.');
        } else {
            $this->warn('Some events failed to replay. Check logs for details.');
        }
    }

    /**
     * Show event processing health report
     */
    private function showHealthReport(): int
    {
        $this->info('=== Event Processing Health Report ===');
        $this->newLine();

        $health = $this->eventReplayService->getHealthReport();

        // Order-to-Cash
        $this->info('Order-to-Cash Flow:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Completed Sales Orders', $health['order_to_cash']['completed_sales_orders']],
                ['With AR Invoices', $health['order_to_cash']['with_ar_invoices']],
                ['Missing AR Invoices', $health['order_to_cash']['missing_invoices']],
            ]
        );

        if ($health['order_to_cash']['missing_invoices'] > 0) {
            $this->warn("Found {$health['order_to_cash']['missing_invoices']} sales orders without AR invoices.");
        } else {
            $this->info('All completed sales orders have AR invoices.');
        }

        $this->newLine();

        // Procure-to-Pay
        $this->info('Procure-to-Pay Flow:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Received Purchase Orders', $health['procure_to_pay']['received_purchase_orders']],
                ['With AP Invoices', $health['procure_to_pay']['with_ap_invoices']],
                ['Missing AP Invoices', $health['procure_to_pay']['missing_invoices']],
            ]
        );

        if ($health['procure_to_pay']['missing_invoices'] > 0) {
            $this->warn("Found {$health['procure_to_pay']['missing_invoices']} purchase orders without AP invoices.");
        } else {
            $this->info('All received purchase orders have AP invoices.');
        }

        $this->newLine();

        // GL Integration
        $this->info('GL Integration:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Posted AR Invoices', $health['gl_integration']['posted_ar_invoices']],
                ['AR with Journal Entries', $health['gl_integration']['ar_with_journal_entries']],
                ['Posted AP Invoices', $health['gl_integration']['posted_ap_invoices']],
                ['AP with Journal Entries', $health['gl_integration']['ap_with_journal_entries']],
            ]
        );

        $arMissing = $health['gl_integration']['posted_ar_invoices'] - $health['gl_integration']['ar_with_journal_entries'];
        $apMissing = $health['gl_integration']['posted_ap_invoices'] - $health['gl_integration']['ap_with_journal_entries'];

        if ($arMissing > 0 || $apMissing > 0) {
            $this->warn("Found {$arMissing} AR invoices and {$apMissing} AP invoices without journal entries.");
        } else {
            $this->info('All posted invoices have journal entries.');
        }

        return Command::SUCCESS;
    }
}
