<?php

namespace Modules\Commissions\Observers;

use Modules\Commissions\Services\CommissionService;
use Modules\Sales\Models\SalesOrder;

/**
 * CommissionSalesOrderObserver
 *
 * Keeps the pending commission row in sync with the order lifecycle.
 * Every branch is a no-op when commissions.enabled is false (checked inside
 * CommissionService).
 */
class CommissionSalesOrderObserver
{
    public function __construct(
        private CommissionService $commissionService
    ) {}

    public function created(SalesOrder $salesOrder): void
    {
        $this->commissionService->syncPendingForOrder($salesOrder);
    }

    public function updated(SalesOrder $salesOrder): void
    {
        // Salesperson assigned or changed after creation
        if ($salesOrder->isDirty('assigned_to')) {
            $this->commissionService->syncPendingForOrder($salesOrder);
        }

        // Order cancelled: cancel commission unless already paid
        if ($salesOrder->isDirty('status') && $salesOrder->status === 'cancelled') {
            $this->commissionService->cancelForOrder($salesOrder);
        }
    }
}
