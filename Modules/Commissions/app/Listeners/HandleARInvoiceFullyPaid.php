<?php

namespace Modules\Commissions\Listeners;

use Modules\Commissions\Services\CommissionService;
use Modules\Finance\Events\ARInvoiceFullyPaid;

/**
 * Marks the order's commission as earned when its AR invoice is fully paid.
 * No-op when commissions.enabled is false (checked inside CommissionService).
 */
class HandleARInvoiceFullyPaid
{
    public function __construct(
        private CommissionService $commissionService
    ) {}

    public function handle(ARInvoiceFullyPaid $event): void
    {
        $this->commissionService->markEarnedForInvoice($event->invoice);
    }
}
