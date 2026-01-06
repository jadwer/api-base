<?php

namespace Modules\Purchase\Services;

use Modules\Purchase\Models\Budget;
use Modules\Purchase\Models\BudgetAllocation;
use Modules\Purchase\Models\PurchaseOrder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * PU-M003: Budget Control Service
 *
 * Handles budget validation, allocation, and tracking for purchase orders.
 */
class BudgetControlService
{
    /**
     * Find applicable budgets for a purchase order.
     */
    public function findApplicableBudgets(PurchaseOrder $order): Collection
    {
        $budgets = collect();

        // Check for supplier-specific budget
        if ($order->contact_id) {
            $supplierBudget = Budget::current()
                ->forSupplier($order->contact_id)
                ->first();

            if ($supplierBudget) {
                $budgets->push($supplierBudget);
            }
        }

        // Check for category budgets based on order items
        if ($order->relationLoaded('purchaseOrderItems') || $order->purchaseOrderItems()->exists()) {
            $categoryIds = $order->purchaseOrderItems()
                ->with('product')
                ->get()
                ->pluck('product.category_id')
                ->filter()
                ->unique();

            foreach ($categoryIds as $categoryId) {
                $categoryBudget = Budget::current()
                    ->forCategory($categoryId)
                    ->first();

                if ($categoryBudget) {
                    $budgets->push($categoryBudget);
                }
            }
        }

        // Check for general budget
        $generalBudget = Budget::current()
            ->where('budget_type', Budget::TYPE_GENERAL)
            ->first();

        if ($generalBudget) {
            $budgets->push($generalBudget);
        }

        return $budgets->unique('id');
    }

    /**
     * Validate if a purchase order can be approved based on budget constraints.
     */
    public function validateBudget(PurchaseOrder $order): array
    {
        $budgets = $this->findApplicableBudgets($order);

        if ($budgets->isEmpty()) {
            return [
                'valid' => true,
                'message' => 'No hay presupuestos aplicables.',
                'budgets' => [],
            ];
        }

        $results = [];
        $hasBlocker = false;
        $requiresApproval = false;
        $warnings = [];

        foreach ($budgets as $budget) {
            $check = $budget->canCommit($order->total_amount);

            $results[] = [
                'budget_id' => $budget->id,
                'budget_name' => $budget->name,
                'budget_code' => $budget->code,
                'budgeted_amount' => $budget->budgeted_amount,
                'current_utilization' => $budget->utilization_percent,
                'new_utilization' => $check['utilization'] ?? null,
                'allowed' => $check['allowed'],
                'reason' => $check['reason'] ?? null,
                'requires_approval' => $check['requires_approval'] ?? false,
            ];

            if (!$check['allowed']) {
                $hasBlocker = true;
            }

            if ($check['requires_approval'] ?? false) {
                $requiresApproval = true;
            }

            if ($check['has_warning'] ?? false) {
                $warnings[] = "Presupuesto '{$budget->name}' alcanzará {$check['utilization']}% de utilización.";
            }
        }

        return [
            'valid' => !$hasBlocker,
            'requires_approval' => $requiresApproval,
            'warnings' => $warnings,
            'message' => $hasBlocker
                ? 'La orden excede los límites de presupuesto.'
                : ($requiresApproval
                    ? 'La orden requiere aprobación por nivel de presupuesto.'
                    : 'La orden cumple con los presupuestos.'),
            'budgets' => $results,
        ];
    }

    /**
     * Allocate a purchase order against applicable budgets.
     */
    public function allocateToBudgets(PurchaseOrder $order): array
    {
        $budgets = $this->findApplicableBudgets($order);
        $allocations = [];

        DB::transaction(function () use ($order, $budgets, &$allocations) {
            foreach ($budgets as $budget) {
                // Check if allocation already exists
                $existing = BudgetAllocation::where('budget_id', $budget->id)
                    ->where('purchase_order_id', $order->id)
                    ->first();

                if ($existing) {
                    $allocations[] = $existing;
                    continue;
                }

                // Create new allocation
                $allocation = BudgetAllocation::create([
                    'budget_id' => $budget->id,
                    'purchase_order_id' => $order->id,
                    'allocated_amount' => $order->total_amount,
                    'status' => BudgetAllocation::STATUS_COMMITTED,
                ]);

                // Update budget committed amount
                $budget->commit($order->total_amount);

                $allocations[] = $allocation;
            }
        });

        return $allocations;
    }

    /**
     * Release allocations when a purchase order is cancelled.
     */
    public function releaseAllocations(PurchaseOrder $order): void
    {
        DB::transaction(function () use ($order) {
            $allocations = BudgetAllocation::where('purchase_order_id', $order->id)
                ->where('status', BudgetAllocation::STATUS_COMMITTED)
                ->with('budget')
                ->get();

            foreach ($allocations as $allocation) {
                $allocation->budget->release($allocation->allocated_amount);
                $allocation->update([
                    'status' => BudgetAllocation::STATUS_CANCELLED,
                    'notes' => 'Liberado por cancelación de orden de compra.',
                ]);
            }
        });
    }

    /**
     * Convert allocations from committed to spent (when invoiced).
     */
    public function markAsSpent(PurchaseOrder $order, float $invoicedAmount): void
    {
        DB::transaction(function () use ($order, $invoicedAmount) {
            $allocations = BudgetAllocation::where('purchase_order_id', $order->id)
                ->where('status', BudgetAllocation::STATUS_COMMITTED)
                ->with('budget')
                ->get();

            foreach ($allocations as $allocation) {
                $spendAmount = min($allocation->allocated_amount, $invoicedAmount);

                $allocation->budget->spend($spendAmount);
                $allocation->update([
                    'status' => BudgetAllocation::STATUS_SPENT,
                    'notes' => "Facturado: \${$spendAmount}",
                ]);
            }
        });
    }

    /**
     * Get budget utilization summary.
     */
    public function getBudgetSummary(?string $periodType = null): array
    {
        $query = Budget::current();

        if ($periodType) {
            $query->where('period_type', $periodType);
        }

        $budgets = $query->get();

        $summary = [
            'total_budgeted' => $budgets->sum('budgeted_amount'),
            'total_committed' => $budgets->sum('committed_amount'),
            'total_spent' => $budgets->sum('spent_amount'),
            'total_available' => $budgets->sum('available_amount'),
            'budgets_count' => $budgets->count(),
            'over_warning' => $budgets->filter(fn ($b) => $b->status_level === 'warning')->count(),
            'over_critical' => $budgets->filter(fn ($b) => $b->status_level === 'critical')->count(),
            'exceeded' => $budgets->filter(fn ($b) => $b->status_level === 'exceeded')->count(),
        ];

        $summary['overall_utilization'] = $summary['total_budgeted'] > 0
            ? round((($summary['total_committed'] + $summary['total_spent']) / $summary['total_budgeted']) * 100, 2)
            : 0;

        return $summary;
    }

    /**
     * Get budgets that need attention (over warning threshold).
     */
    public function getBudgetsNeedingAttention(): Collection
    {
        return Budget::current()
            ->overWarning()
            ->orderByRaw('(committed_amount + spent_amount) / budgeted_amount DESC')
            ->get();
    }

    /**
     * Create a budget for a new period (e.g., monthly rollover).
     */
    public function createNextPeriodBudget(Budget $sourceBudget): Budget
    {
        $newStartDate = $sourceBudget->end_date->addDay();

        $newEndDate = match ($sourceBudget->period_type) {
            Budget::PERIOD_MONTHLY => $newStartDate->copy()->endOfMonth(),
            Budget::PERIOD_QUARTERLY => $newStartDate->copy()->addMonths(3)->subDay(),
            Budget::PERIOD_ANNUAL => $newStartDate->copy()->addYear()->subDay(),
            default => $newStartDate->copy()->addMonth(),
        };

        return Budget::create([
            'name' => $sourceBudget->name,
            'code' => $sourceBudget->code . '-' . $newStartDate->format('Ym'),
            'description' => $sourceBudget->description,
            'budget_type' => $sourceBudget->budget_type,
            'department_code' => $sourceBudget->department_code,
            'category_id' => $sourceBudget->category_id,
            'project_code' => $sourceBudget->project_code,
            'contact_id' => $sourceBudget->contact_id,
            'period_type' => $sourceBudget->period_type,
            'start_date' => $newStartDate,
            'end_date' => $newEndDate,
            'fiscal_year' => $newStartDate->year,
            'budgeted_amount' => $sourceBudget->budgeted_amount,
            'warning_threshold' => $sourceBudget->warning_threshold,
            'critical_threshold' => $sourceBudget->critical_threshold,
            'hard_limit' => $sourceBudget->hard_limit,
            'allow_overcommit' => $sourceBudget->allow_overcommit,
            'is_active' => true,
        ]);
    }
}
