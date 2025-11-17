<?php

namespace Modules\Purchase\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Contacts\Models\Contact;

/**
 * PU-001: Purchase Order Approval Workflow Service
 *
 * Implements three-tier approval workflow for purchase orders:
 * - Tier 1: Procurement Manager (>50,000)
 * - Tier 2: Finance Director (>250,000)
 * - Tier 3: CFO (>1,000,000)
 */
class PurchaseOrderApprovalService
{
    /**
     * Check if Purchase Order requires approval
     *
     * @param PurchaseOrder $order
     * @return bool
     */
    public function requiresApproval(PurchaseOrder $order): bool
    {
        $rules = [
            // Rule 1: Amount threshold (>50,000)
            $order->total_amount > config('purchase.approval.thresholds.tier_1', 50000),

            // Rule 2: First-time supplier (no payment history)
            config('purchase.approval.triggers.first_time_supplier', true) && $this->isFirstTimeSupplier($order->contact_id),

            // Rule 3: High-value items (any single item >100,000)
            $this->hasHighValueItems($order),
        ];

        return collect($rules)->contains(true);
    }

    /**
     * Get required approvers for Purchase Order
     *
     * @param PurchaseOrder $order
     * @return Collection
     */
    public function getRequiredApprovers(PurchaseOrder $order): Collection
    {
        $approvers = collect();

        $tier1Threshold = config('purchase.approval.thresholds.tier_1', 50000);
        $tier2Threshold = config('purchase.approval.thresholds.tier_2', 250000);
        $tier3Threshold = config('purchase.approval.thresholds.tier_3', 1000000);

        // Tier 1: Procurement Manager (>50,000)
        if ($order->total_amount > $tier1Threshold) {
            $approvers->push([
                'role' => 'procurement_manager',
                'permission' => 'purchase.approve-tier1',
                'tier' => 1,
                'reason' => "Amount exceeds " . number_format($tier1Threshold),
            ]);
        }

        // Tier 2: Finance Director (>250,000)
        if ($order->total_amount > $tier2Threshold) {
            $approvers->push([
                'role' => 'finance_director',
                'permission' => 'purchase.approve-tier2',
                'tier' => 2,
                'reason' => "Amount exceeds " . number_format($tier2Threshold),
            ]);
        }

        // Tier 3: CFO (>1,000,000)
        if ($order->total_amount > $tier3Threshold) {
            $approvers->push([
                'role' => 'cfo',
                'permission' => 'purchase.approve-tier3',
                'tier' => 3,
                'reason' => "Amount exceeds " . number_format($tier3Threshold),
            ]);
        }

        // Procurement: First-time supplier
        if (config('purchase.approval.triggers.first_time_supplier', true) && $this->isFirstTimeSupplier($order->contact_id)) {
            $approvers->push([
                'role' => 'procurement',
                'permission' => 'purchase.approve-new-supplier',
                'tier' => 1,
                'reason' => 'First-time supplier',
            ]);
        }

        return $approvers;
    }

    /**
     * Approve Purchase Order
     *
     * @param PurchaseOrder $order
     * @param int $userId
     * @param string $notes
     * @return bool
     * @throws \Exception
     */
    public function approve(PurchaseOrder $order, int $userId, string $notes = ''): bool
    {
        // Validate current status
        if (!in_array($order->status, ['pending'])) {
            throw new \Exception("Only pending orders can be approved. Current status: {$order->status}");
        }

        return DB::transaction(function () use ($order, $userId, $notes) {
            // Check if approval is required
            if (!$this->requiresApproval($order)) {
                // Auto-approve if no approval needed
                $order->update([
                    'approval_status' => 'not_required',
                    'status' => 'approved',
                ]);

                Log::info('Purchase order auto-approved (no approval required)', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number ?? 'N/A',
                    'total_amount' => $order->total_amount,
                ]);

                return true;
            }

            // Get current approvals
            $metadata = $order->metadata ?? [];
            $approvals = $metadata['approvals'] ?? [];

            // Add new approval
            $approvals[] = [
                'user_id' => $userId,
                'approved_at' => now()->toDateTimeString(),
                'notes' => $notes,
            ];

            // Update metadata
            $metadata['approvals'] = $approvals;

            // Check if all required approvers have approved
            $requiredApprovers = $this->getRequiredApprovers($order);
            $allApproved = count($approvals) >= $requiredApprovers->count();

            if ($allApproved) {
                $order->update([
                    'approval_status' => 'approved',
                    'approved_at' => now(),
                    'approved_by_id' => $userId,
                    'status' => 'approved',
                    'metadata' => $metadata,
                ]);

                Log::info('Purchase order fully approved', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number ?? 'N/A',
                    'total_amount' => $order->total_amount,
                    'approved_by' => $userId,
                    'approval_count' => count($approvals),
                ]);
            } else {
                $order->update([
                    'approval_status' => 'pending',
                    'metadata' => $metadata,
                ]);

                Log::info('Purchase order partially approved', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number ?? 'N/A',
                    'approval_count' => count($approvals),
                    'required_count' => $requiredApprovers->count(),
                ]);
            }

            return $allApproved;
        });
    }

    /**
     * Reject Purchase Order
     *
     * @param PurchaseOrder $order
     * @param int $userId
     * @param string $reason
     * @return bool
     */
    public function reject(PurchaseOrder $order, int $userId, string $reason): bool
    {
        // Validate current status
        if (!in_array($order->status, ['pending'])) {
            throw new \Exception("Only pending orders can be rejected. Current status: {$order->status}");
        }

        $metadata = $order->metadata ?? [];
        $metadata['rejection'] = [
            'user_id' => $userId,
            'rejected_at' => now()->toDateTimeString(),
            'reason' => $reason,
        ];

        $order->update([
            'approval_status' => 'rejected',
            'status' => 'cancelled',
            'metadata' => $metadata,
        ]);

        Log::info('Purchase order rejected', [
            'order_id' => $order->id,
            'order_number' => $order->order_number ?? 'N/A',
            'rejected_by' => $userId,
            'reason' => $reason,
        ]);

        return true;
    }

    /**
     * Check if this is supplier's first purchase order
     *
     * @param int $contactId
     * @return bool
     */
    private function isFirstTimeSupplier(int $contactId): bool
    {
        $orderCount = PurchaseOrder::where('contact_id', $contactId)
            ->whereIn('status', ['received', 'approved'])
            ->count();

        return $orderCount === 0;
    }

    /**
     * Check if order has any high-value items (>100,000 per item)
     *
     * @param PurchaseOrder $order
     * @return bool
     */
    private function hasHighValueItems(PurchaseOrder $order): bool
    {
        $highValueThreshold = config('purchase.approval.triggers.high_value_item_threshold', 100000);

        // Load items if not already loaded
        if (!$order->relationLoaded('purchaseOrderItems')) {
            $order->load('purchaseOrderItems');
        }

        foreach ($order->purchaseOrderItems as $item) {
            if ($item->total > $highValueThreshold) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get approval history for order
     *
     * @param PurchaseOrder $order
     * @return Collection
     */
    public function getApprovalHistory(PurchaseOrder $order): Collection
    {
        $metadata = $order->metadata ?? [];
        $approvals = $metadata['approvals'] ?? [];

        return collect($approvals)->map(function ($approval) {
            return [
                'user_id' => $approval['user_id'],
                'approved_at' => $approval['approved_at'],
                'notes' => $approval['notes'] ?? '',
            ];
        });
    }

    /**
     * Get pending approvals count
     *
     * @return int
     */
    public function getPendingApprovalsCount(): int
    {
        return PurchaseOrder::where('approval_status', 'pending')
            ->count();
    }
}
