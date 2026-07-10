<?php

namespace Modules\Commissions\Services;

use Illuminate\Support\Facades\Log;
use Modules\AppConfig\Models\AppSetting;
use Modules\Commissions\Models\Commission;
use Modules\Contacts\Models\Contact;
use Modules\Finance\Models\ARInvoice;
use Modules\Sales\Models\SalesOrder;
use Modules\User\Models\User;

/**
 * CommissionService
 *
 * Single place for the commissions business rules (WS5):
 * - Feature flag per tenant: AppSetting commissions.enabled (default false).
 * - Pct resolution: contact.commission_pct_override > user.commission_pct >
 *   AppSetting commissions.default_pct. Resolved ONLY here (resolvePct).
 * - v1 basis = collected: a commission is earned when the AR invoice linked
 *   to the order becomes fully paid. Partial payments never earn.
 * - Idempotency: rows keyed by UNIQUE(sales_order_id, user_id).
 */
class CommissionService
{
    public function isEnabled(): bool
    {
        return AppSetting::getBoolean('commissions.enabled', false);
    }

    /**
     * Resolve the applicable commission percentage.
     *
     * contact.commission_pct_override > user.commission_pct > commissions.default_pct
     */
    public function resolvePct(?Contact $contact, ?User $user): float
    {
        $pct = $contact?->commission_pct_override
            ?? $user?->commission_pct
            ?? AppSetting::get('commissions.default_pct', 5.0);

        return (float) $pct;
    }

    /**
     * Resolve the salesperson for an order: assigned_to first, then the
     * contact's default salesperson.
     */
    public function resolveSalespersonId(SalesOrder $order): ?int
    {
        return $order->assigned_to ?? $order->contact?->default_salesperson_id;
    }

    /**
     * Create or refresh the pending commission row for an order.
     *
     * No-op when the feature is disabled or the order has no salesperson.
     * Never touches rows that already moved past pending (earned/paid/cancelled).
     */
    public function syncPendingForOrder(SalesOrder $order): ?Commission
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $userId = $this->resolveSalespersonId($order);

        if ($userId === null) {
            return null;
        }

        $existing = Commission::where('sales_order_id', $order->id)
            ->where('user_id', $userId)
            ->first();

        if ($existing && !$existing->isPending()) {
            return $existing;
        }

        $pct = $this->resolvePct($order->contact, User::find($userId));
        $baseAmount = (float) $order->total_amount;

        return Commission::updateOrCreate(
            ['sales_order_id' => $order->id, 'user_id' => $userId],
            [
                'contact_id' => $order->contact_id,
                'base_amount' => $baseAmount,
                'commission_pct' => $pct,
                'commission_amount' => round($baseAmount * $pct / 100, 2),
                'status' => Commission::STATUS_PENDING,
            ]
        );
    }

    /**
     * Mark commissions of the invoice's order as earned.
     *
     * base_amount is recalculated from the invoice total (collected basis);
     * commission_pct stays frozen as stored in the row. Idempotent: rows
     * already earned or paid are left untouched.
     */
    public function markEarnedForInvoice(ARInvoice $invoice): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        if (!$invoice->sales_order_id) {
            return;
        }

        $commissions = Commission::where('sales_order_id', $invoice->sales_order_id)->get();

        foreach ($commissions as $commission) {
            if (!$commission->isPending()) {
                continue;
            }

            $baseAmount = (float) $invoice->total_amount;

            $commission->update([
                'status' => Commission::STATUS_EARNED,
                'earned_at' => now(),
                'ar_invoice_id' => $invoice->id,
                'base_amount' => $baseAmount,
                'commission_amount' => round($baseAmount * $commission->commission_pct / 100, 2),
            ]);
        }
    }

    /**
     * Revert earned commissions back to pending when the invoice stops being
     * fully paid. Paid commissions are NOT touched (manual adjustment needed).
     */
    public function revertForInvoice(ARInvoice $invoice): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        if (!$invoice->sales_order_id) {
            return;
        }

        $commissions = Commission::where('sales_order_id', $invoice->sales_order_id)->get();

        foreach ($commissions as $commission) {
            if ($commission->isPaid()) {
                Log::warning('Payment reversed on an AR invoice with an already paid commission; manual adjustment required', [
                    'commission_id' => $commission->id,
                    'sales_order_id' => $commission->sales_order_id,
                    'ar_invoice_id' => $invoice->id,
                    'user_id' => $commission->user_id,
                ]);
                continue;
            }

            if ($commission->isEarned()) {
                $commission->update([
                    'status' => Commission::STATUS_PENDING,
                    'earned_at' => null,
                ]);
            }
        }
    }

    /**
     * Cancel commissions of a cancelled order, except paid ones.
     */
    public function cancelForOrder(SalesOrder $order): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        Commission::where('sales_order_id', $order->id)
            ->where('status', '!=', Commission::STATUS_PAID)
            ->update(['status' => Commission::STATUS_CANCELLED]);
    }
}
