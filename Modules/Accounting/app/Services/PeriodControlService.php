<?php

namespace Modules\Accounting\Services;

use Modules\Accounting\Models\FiscalPeriod;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PeriodControlService
{
    /**
     * Validate if period allows the specified operation
     *
     * @param Carbon $date
     * @param string $operation
     * @return bool
     * @throws \Exception
     */
    public function validatePeriodAccess(Carbon $date, string $operation = 'post'): bool
    {
        $period = $this->findPeriodByDate($date);

        if (!$period) {
            throw new \Exception(
                "No fiscal period found for date {$date->toDateString()}. " .
                "Please create fiscal periods before posting."
            );
        }

        // Hard lock - no modifications allowed (closed period)
        if ($period->status === 'closed') {
            throw new \Exception(
                "Period {$period->name} is closed. No modifications allowed. " .
                "Please contact system administrator to reopen."
            );
        }

        // Soft lock - only authorized users can post
        if ($period->status === 'locked') {
            $user = auth()->user();

            if (!$user) {
                throw new \Exception("Authentication required for locked period operations.");
            }

            if (!$user->hasPermissionTo('accounting.period-override')) {
                throw new \Exception(
                    "Period {$period->name} is locked. " .
                    "You need 'accounting.period-override' permission to post."
                );
            }
        }

        // Future period restrictions
        if ($date->gt(now()) && !$this->allowFuturePosting($operation)) {
            throw new \Exception(
                "Future date posting not allowed for operation: {$operation}. " .
                "Date: {$date->toDateString()}, Today: " . now()->toDateString()
            );
        }

        // Past period restrictions (more than 2 fiscal periods ago)
        $currentPeriod = $this->getCurrentPeriod();
        if ($currentPeriod) {
            $periodDiff = $this->calculatePeriodDifference($period, $currentPeriod);

            if ($periodDiff > 2) {
                throw new \Exception(
                    "Cannot post to period {$period->name}. " .
                    "More than 2 periods in the past. Current period: {$currentPeriod->name}"
                );
            }
        }

        return true;
    }

    /**
     * Find fiscal period by date
     *
     * @param Carbon $date
     * @return FiscalPeriod|null
     */
    public function findPeriodByDate(Carbon $date): ?FiscalPeriod
    {
        return FiscalPeriod::where('start_date', '<=', $date->toDateString())
            ->where('end_date', '>=', $date->toDateString())
            ->first();
    }

    /**
     * Get current fiscal period
     *
     * @return FiscalPeriod|null
     */
    public function getCurrentPeriod(): ?FiscalPeriod
    {
        return $this->findPeriodByDate(now());
    }

    /**
     * Lock fiscal period (soft lock - requires permission to post)
     *
     * @param FiscalPeriod $period
     * @param int $userId
     * @return bool
     * @throws \Exception
     */
    public function lockPeriod(FiscalPeriod $period, int $userId): bool
    {
        if ($period->status === 'closed') {
            throw new \Exception("Cannot lock a closed period. Period is already closed.");
        }

        return DB::transaction(function () use ($period, $userId) {
            $period->update([
                'status' => 'locked',
                'locked_at' => now(),
                'locked_by_id' => $userId,
            ]);

            // Log the action
            activity()
                ->performedOn($period)
                ->causedBy($userId)
                ->withProperties(['old_status' => 'open', 'new_status' => 'locked'])
                ->log('Fiscal period locked');

            return true;
        });
    }

    /**
     * Unlock fiscal period
     *
     * @param FiscalPeriod $period
     * @param int $userId
     * @return bool
     * @throws \Exception
     */
    public function unlockPeriod(FiscalPeriod $period, int $userId): bool
    {
        if ($period->status === 'closed') {
            throw new \Exception("Cannot unlock a closed period. Use reopen instead.");
        }

        if ($period->status !== 'locked') {
            throw new \Exception("Period is not locked.");
        }

        return DB::transaction(function () use ($period, $userId) {
            $period->update([
                'status' => 'open',
                'locked_at' => null,
                'locked_by_id' => null,
            ]);

            // Log the action
            activity()
                ->performedOn($period)
                ->causedBy($userId)
                ->withProperties(['old_status' => 'locked', 'new_status' => 'open'])
                ->log('Fiscal period unlocked');

            return true;
        });
    }

    /**
     * Close fiscal period (hard lock - no modifications allowed)
     *
     * @param FiscalPeriod $period
     * @param int $userId
     * @return bool
     * @throws \Exception
     */
    public function closePeriod(FiscalPeriod $period, int $userId): bool
    {
        // Validate period can be closed
        $this->validatePeriodCanBeClosed($period);

        return DB::transaction(function () use ($period, $userId) {
            $period->update([
                'status' => 'closed',
                'closed_at' => now(),
                'closed_by_id' => $userId,
            ]);

            // Log the action
            activity()
                ->performedOn($period)
                ->causedBy($userId)
                ->withProperties(['status' => 'closed'])
                ->log('Fiscal period closed');

            return true;
        });
    }

    /**
     * Reopen fiscal period
     *
     * @param FiscalPeriod $period
     * @param int $userId
     * @param string $reason
     * @return bool
     * @throws \Exception
     */
    public function reopenPeriod(FiscalPeriod $period, int $userId, string $reason): bool
    {
        if ($period->status !== 'closed') {
            throw new \Exception("Only closed periods can be reopened.");
        }

        return DB::transaction(function () use ($period, $userId, $reason) {
            $period->update([
                'status' => 'open',
                'closed_at' => null,
                'closed_by_id' => null,
            ]);

            // Log the action with reason
            activity()
                ->performedOn($period)
                ->causedBy($userId)
                ->withProperties([
                    'old_status' => 'closed',
                    'new_status' => 'open',
                    'reason' => $reason,
                ])
                ->log('Fiscal period reopened');

            return true;
        });
    }

    /**
     * Validate if period can be closed
     *
     * @param FiscalPeriod $period
     * @return bool
     * @throws \Exception
     */
    private function validatePeriodCanBeClosed(FiscalPeriod $period): bool
    {
        // Check if period has unposted entries
        $unpostedCount = DB::table('journal_entries')
            ->where('fiscal_period_id', $period->id)
            ->where('status', 'draft')
            ->count();

        if ($unpostedCount > 0) {
            throw new \Exception(
                "Cannot close period. Found {$unpostedCount} unposted journal entries. " .
                "Post or delete draft entries before closing."
            );
        }

        // Check if all journal entries are balanced
        $unbalancedCount = DB::table('journal_entries')
            ->where('fiscal_period_id', $period->id)
            ->where('status', 'posted')
            ->whereRaw('ABS(total_debit - total_credit) > 0.01')
            ->count();

        if ($unbalancedCount > 0) {
            throw new \Exception(
                "Cannot close period. Found {$unbalancedCount} unbalanced journal entries. " .
                "Fix unbalanced entries before closing."
            );
        }

        return true;
    }

    /**
     * Check if future posting is allowed for operation
     *
     * @param string $operation
     * @return bool
     */
    private function allowFuturePosting(string $operation): bool
    {
        $allowedOperations = config('accounting.future_posting_allowed', ['budget', 'forecast']);

        return in_array($operation, $allowedOperations);
    }

    /**
     * Calculate difference between two periods (in months)
     *
     * @param FiscalPeriod $period1
     * @param FiscalPeriod $period2
     * @return int
     */
    private function calculatePeriodDifference(FiscalPeriod $period1, FiscalPeriod $period2): int
    {
        $date1 = Carbon::parse($period1->start_date);
        $date2 = Carbon::parse($period2->start_date);

        return abs($date1->diffInMonths($date2));
    }

    /**
     * Get period statistics
     *
     * @param FiscalPeriod $period
     * @return array
     */
    public function getPeriodStatistics(FiscalPeriod $period): array
    {
        $entries = DB::table('journal_entries')
            ->where('fiscal_period_id', $period->id)
            ->selectRaw('
                COUNT(*) as total_entries,
                SUM(CASE WHEN status = "posted" THEN 1 ELSE 0 END) as posted_entries,
                SUM(CASE WHEN status = "draft" THEN 1 ELSE 0 END) as draft_entries,
                SUM(total_debit) as total_debit,
                SUM(total_credit) as total_credit
            ')
            ->first();

        return [
            'period_name' => $period->name,
            'period_status' => $period->status,
            'start_date' => $period->start_date,
            'end_date' => $period->end_date,
            'total_entries' => $entries->total_entries ?? 0,
            'posted_entries' => $entries->posted_entries ?? 0,
            'draft_entries' => $entries->draft_entries ?? 0,
            'total_debit' => $entries->total_debit ?? 0,
            'total_credit' => $entries->total_credit ?? 0,
            'is_balanced' => abs(($entries->total_debit ?? 0) - ($entries->total_credit ?? 0)) < 0.01,
        ];
    }

    /**
     * Get all open periods
     *
     * @return \Illuminate\Support\Collection
     */
    public function getOpenPeriods()
    {
        return FiscalPeriod::where('status', 'open')
            ->orderBy('start_date')
            ->get();
    }

    /**
     * Get all locked periods
     *
     * @return \Illuminate\Support\Collection
     */
    public function getLockedPeriods()
    {
        return FiscalPeriod::where('status', 'locked')
            ->orderBy('start_date')
            ->get();
    }

    /**
     * Get all closed periods
     *
     * @return \Illuminate\Support\Collection
     */
    public function getClosedPeriods()
    {
        return FiscalPeriod::where('status', 'closed')
            ->orderBy('start_date', 'desc')
            ->get();
    }
}
