<?php

namespace Modules\Commissions\Listeners;

use Modules\Commissions\Services\CommissionService;
use Modules\Finance\Events\ARInvoicePaymentReversed;

/**
 * Reverts an earned commission to pending when its AR invoice stops being
 * fully paid. Paid commissions are never touched (warning logged instead).
 * No-op when commissions.enabled is false (checked inside CommissionService).
 */
class HandleARInvoicePaymentReversed
{
    public function __construct(
        private CommissionService $commissionService
    ) {}

    public function handle(ARInvoicePaymentReversed $event): void
    {
        $this->commissionService->revertForInvoice($event->invoice);
    }
}
