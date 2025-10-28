<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Events\SalesOrderCompleted;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Events\PurchaseOrderReceived;
use Modules\Finance\Models\ARInvoice;
use Modules\Finance\Models\APInvoice;
use Modules\Finance\Events\ARInvoicePosted;
use Modules\Finance\Events\APInvoicePosted;
use Carbon\Carbon;

class EventReplayService
{
    /**
     * Replay failed Order-to-Cash events (Sales Order → AR Invoice)
     *
     * Finds sales orders that are completed but don't have AR invoices
     * and replays the SalesOrderCompleted event
     *
     * @param Carbon|null $since Only replay orders since this date
     * @return array
     */
    public function replayOrderToCash(?Carbon $since = null): array
    {
        $query = SalesOrder::where('status', 'completed')
            ->whereDoesntHave('arInvoices', function ($q) {
                $q->whereIn('status', ['draft', 'posted']);
            });

        if ($since) {
            $query->where('updated_at', '>=', $since);
        }

        $missingSalesOrders = $query->get();

        $results = [
            'total' => $missingSalesOrders->count(),
            'processed' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($missingSalesOrders as $salesOrder) {
            try {
                DB::beginTransaction();

                // Replay the event
                event(new SalesOrderCompleted($salesOrder));

                DB::commit();

                $results['processed']++;

                Log::info('Order-to-Cash event replayed', [
                    'sales_order_id' => $salesOrder->id,
                    'order_number' => $salesOrder->order_number,
                ]);
            } catch (\Exception $e) {
                DB::rollBack();

                $results['failed']++;
                $results['errors'][] = [
                    'sales_order_id' => $salesOrder->id,
                    'order_number' => $salesOrder->order_number,
                    'error' => $e->getMessage(),
                ];

                Log::error('Order-to-Cash event replay failed', [
                    'sales_order_id' => $salesOrder->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Replay failed Procure-to-Pay events (Purchase Order → AP Invoice)
     *
     * Finds purchase orders that are received but don't have AP invoices
     * and replays the PurchaseOrderReceived event
     *
     * @param Carbon|null $since Only replay orders since this date
     * @return array
     */
    public function replayProcureToPay(?Carbon $since = null): array
    {
        $query = PurchaseOrder::where('status', 'received')
            ->whereDoesntHave('apInvoices', function ($q) {
                $q->whereIn('status', ['draft', 'posted']);
            });

        if ($since) {
            $query->where('updated_at', '>=', $since);
        }

        $missingPurchaseOrders = $query->get();

        $results = [
            'total' => $missingPurchaseOrders->count(),
            'processed' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($missingPurchaseOrders as $purchaseOrder) {
            try {
                DB::beginTransaction();

                // Replay the event
                event(new PurchaseOrderReceived($purchaseOrder));

                DB::commit();

                $results['processed']++;

                Log::info('Procure-to-Pay event replayed', [
                    'purchase_order_id' => $purchaseOrder->id,
                    'order_number' => $purchaseOrder->order_number,
                ]);
            } catch (\Exception $e) {
                DB::rollBack();

                $results['failed']++;
                $results['errors'][] = [
                    'purchase_order_id' => $purchaseOrder->id,
                    'order_number' => $purchaseOrder->order_number,
                    'error' => $e->getMessage(),
                ]);

                Log::error('Procure-to-Pay event replay failed', [
                    'purchase_order_id' => $purchaseOrder->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Replay AR Invoice Posted events for invoices that were posted but didn't trigger GL updates
     *
     * @param Carbon|null $since Only replay invoices since this date
     * @return array
     */
    public function replayARInvoicePosted(?Carbon $since = null): array
    {
        $query = ARInvoice::where('status', 'posted')
            ->whereDoesntHave('journalEntries', function ($q) {
                $q->where('status', 'posted');
            });

        if ($since) {
            $query->where('updated_at', '>=', $since);
        }

        $missingGLInvoices = $query->get();

        $results = [
            'total' => $missingGLInvoices->count(),
            'processed' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($missingGLInvoices as $invoice) {
            try {
                DB::beginTransaction();

                // Replay the event
                event(new ARInvoicePosted($invoice));

                DB::commit();

                $results['processed']++;

                Log::info('AR Invoice Posted event replayed', [
                    'ar_invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                ]);
            } catch (\Exception $e) {
                DB::rollBack();

                $results['failed']++;
                $results['errors'][] = [
                    'ar_invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'error' => $e->getMessage(),
                ];

                Log::error('AR Invoice Posted event replay failed', [
                    'ar_invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Replay AP Invoice Posted events for invoices that were posted but didn't trigger GL updates
     *
     * @param Carbon|null $since Only replay invoices since this date
     * @return array
     */
    public function replayAPInvoicePosted(?Carbon $since = null): array
    {
        $query = APInvoice::where('status', 'posted')
            ->whereDoesntHave('journalEntries', function ($q) {
                $q->where('status', 'posted');
            });

        if ($since) {
            $query->where('updated_at', '>=', $since);
        }

        $missingGLInvoices = $query->get();

        $results = [
            'total' => $missingGLInvoices->count(),
            'processed' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($missingGLInvoices as $invoice) {
            try {
                DB::beginTransaction();

                // Replay the event
                event(new APInvoicePosted($invoice));

                DB::commit();

                $results['processed']++;

                Log::info('AP Invoice Posted event replayed', [
                    'ap_invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                ]);
            } catch (\Exception $e) {
                DB::rollBack();

                $results['failed']++;
                $results['errors'][] = [
                    'ap_invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'error' => $e->getMessage(),
                ];

                Log::error('AP Invoice Posted event replay failed', [
                    'ap_invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Replay all failed events across all flows
     *
     * @param Carbon|null $since Only replay events since this date
     * @return array
     */
    public function replayAll(?Carbon $since = null): array
    {
        $results = [
            'order_to_cash' => $this->replayOrderToCash($since),
            'procure_to_pay' => $this->replayProcureToPay($since),
            'ar_invoice_posted' => $this->replayARInvoicePosted($since),
            'ap_invoice_posted' => $this->replayAPInvoicePosted($since),
        ];

        $totals = [
            'total' => array_sum(array_column($results, 'total')),
            'processed' => array_sum(array_column($results, 'processed')),
            'failed' => array_sum(array_column($results, 'failed')),
            'by_flow' => $results,
        ];

        Log::info('Event replay completed', $totals);

        return $totals;
    }

    /**
     * Get event processing health report
     *
     * @return array
     */
    public function getHealthReport(): array
    {
        return [
            'order_to_cash' => [
                'completed_sales_orders' => SalesOrder::where('status', 'completed')->count(),
                'with_ar_invoices' => SalesOrder::where('status', 'completed')
                    ->has('arInvoices')
                    ->count(),
                'missing_invoices' => SalesOrder::where('status', 'completed')
                    ->whereDoesntHave('arInvoices')
                    ->count(),
            ],
            'procure_to_pay' => [
                'received_purchase_orders' => PurchaseOrder::where('status', 'received')->count(),
                'with_ap_invoices' => PurchaseOrder::where('status', 'received')
                    ->has('apInvoices')
                    ->count(),
                'missing_invoices' => PurchaseOrder::where('status', 'received')
                    ->whereDoesntHave('apInvoices')
                    ->count(),
            ],
            'gl_integration' => [
                'posted_ar_invoices' => ARInvoice::where('status', 'posted')->count(),
                'ar_with_journal_entries' => ARInvoice::where('status', 'posted')
                    ->has('journalEntries')
                    ->count(),
                'posted_ap_invoices' => APInvoice::where('status', 'posted')->count(),
                'ap_with_journal_entries' => APInvoice::where('status', 'posted')
                    ->has('journalEntries')
                    ->count(),
            ],
        ];
    }
}
