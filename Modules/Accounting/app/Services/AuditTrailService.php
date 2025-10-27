<?php

namespace Modules\Accounting\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class AuditTrailService
{
    /**
     * Critical actions that require enhanced logging and retention
     */
    private const CRITICAL_ACTIONS = [
        'posted',
        'reversed',
        'voided',
        'approved',
        'rejected',
        'period_closed',
        'period_reopened',
    ];

    /**
     * Log financial transaction with full audit trail
     *
     * @param Model $model
     * @param string $action
     * @param array $changes
     * @param array $metadata
     * @return Activity
     */
    public function logFinancialTransaction(
        Model $model,
        string $action,
        array $changes = [],
        array $metadata = []
    ): Activity {
        $activity = activity()
            ->performedOn($model)
            ->causedBy(auth()->id())
            ->withProperties([
                'changes' => $changes,
                'metadata' => $metadata,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'session_id' => session()->getId(),
                'timestamp' => now()->toDateTimeString(),
            ])
            ->log($action);

        // Critical actions require additional logging
        if (in_array($action, self::CRITICAL_ACTIONS)) {
            $this->logCriticalAction($model, $action, $changes, $activity);
        }

        return $activity;
    }

    /**
     * Log critical action with enhanced retention and verification
     *
     * @param Model $model
     * @param string $action
     * @param array $changes
     * @param Activity $activity
     * @return void
     */
    private function logCriticalAction(Model $model, string $action, array $changes, Activity $activity): void
    {
        DB::table('critical_action_logs')->insert([
            'activity_id' => $activity->id,
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'action' => $action,
            'user_id' => auth()->id(),
            'changes_snapshot' => json_encode($changes),
            'model_snapshot' => json_encode($model->toArray()),
            'requires_retention' => true,
            'retention_years' => $this->getRetentionYears($action),
            'verification_hash' => $this->generateVerificationHash($model, $action, $changes),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * Get retention years for action (Mexican fiscal requirement: 7 years minimum)
     *
     * @param string $action
     * @return int
     */
    private function getRetentionYears(string $action): int
    {
        return match ($action) {
            'posted', 'approved' => 7, // Mexican fiscal requirement
            'reversed', 'voided' => 10, // Enhanced retention for reversals
            'period_closed' => 15, // Long-term retention for period closures
            default => 7
        };
    }

    /**
     * Generate verification hash for audit integrity
     *
     * @param Model $model
     * @param string $action
     * @param array $changes
     * @return string
     */
    private function generateVerificationHash(Model $model, string $action, array $changes): string
    {
        $data = [
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'action' => $action,
            'user_id' => auth()->id(),
            'timestamp' => now()->toDateTimeString(),
            'changes' => $changes,
        ];

        return hash('sha256', json_encode($data) . config('app.key'));
    }

    /**
     * Verify audit trail integrity
     *
     * @param int $activityId
     * @return bool
     */
    public function verifyAuditIntegrity(int $activityId): bool
    {
        $criticalLog = DB::table('critical_action_logs')
            ->where('activity_id', $activityId)
            ->first();

        if (!$criticalLog) {
            return false; // Not a critical action
        }

        $activity = Activity::find($activityId);

        if (!$activity) {
            return false;
        }

        // Regenerate hash and compare
        $model = $activity->subject;
        $changes = json_decode($criticalLog->changes_snapshot, true);

        $expectedHash = $this->generateVerificationHash(
            $model,
            $criticalLog->action,
            $changes
        );

        return hash_equals($criticalLog->verification_hash, $expectedHash);
    }

    /**
     * Get audit trail for model
     *
     * @param Model $model
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function getAuditTrail(Model $model, int $limit = 50)
    {
        return Activity::where('subject_type', get_class($model))
            ->where('subject_id', $model->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'action' => $activity->description,
                    'user_id' => $activity->causer_id,
                    'user_name' => $activity->causer?->name ?? 'System',
                    'changes' => $activity->properties['changes'] ?? [],
                    'ip_address' => $activity->properties['ip_address'] ?? null,
                    'timestamp' => $activity->created_at->toDateTimeString(),
                    'is_critical' => in_array($activity->description, self::CRITICAL_ACTIONS),
                ];
            });
    }

    /**
     * Get critical actions log
     *
     * @param string|null $modelType
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function getCriticalActionsLog(?string $modelType = null, int $limit = 100)
    {
        $query = DB::table('critical_action_logs')
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        if ($modelType) {
            $query->where('model_type', $modelType);
        }

        return collect($query->get());
    }

    /**
     * Get user activity summary
     *
     * @param int $userId
     * @param int $days
     * @return array
     */
    public function getUserActivitySummary(int $userId, int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $activities = Activity::where('causer_id', $userId)
            ->where('created_at', '>=', $startDate)
            ->get();

        $criticalActions = $activities->filter(function ($activity) {
            return in_array($activity->description, self::CRITICAL_ACTIONS);
        });

        return [
            'user_id' => $userId,
            'period_days' => $days,
            'total_actions' => $activities->count(),
            'critical_actions' => $criticalActions->count(),
            'actions_by_type' => $activities->groupBy('description')
                ->map->count()
                ->toArray(),
            'last_activity' => $activities->first()?->created_at?->toDateTimeString(),
        ];
    }

    /**
     * Get compliance report for retention requirements
     *
     * @return array
     */
    public function getComplianceReport(): array
    {
        $logs = DB::table('critical_action_logs')
            ->select([
                'action',
                DB::raw('COUNT(*) as count'),
                DB::raw('MIN(created_at) as oldest_record'),
                DB::raw('MAX(created_at) as newest_record'),
            ])
            ->groupBy('action')
            ->get();

        $retentionStatus = [];

        foreach ($logs as $log) {
            $oldestDate = new \DateTime($log->oldest_record);
            $age = $oldestDate->diff(now())->y;
            $required = $this->getRetentionYears($log->action);

            $retentionStatus[] = [
                'action' => $log->action,
                'count' => $log->count,
                'oldest_record' => $log->oldest_record,
                'age_years' => $age,
                'retention_required' => $required,
                'compliant' => $age <= $required,
            ];
        }

        return [
            'total_critical_logs' => array_sum(array_column($retentionStatus, 'count')),
            'retention_status' => $retentionStatus,
            'compliance_rate' => $this->calculateComplianceRate($retentionStatus),
        ];
    }

    /**
     * Calculate compliance rate
     *
     * @param array $retentionStatus
     * @return float
     */
    private function calculateComplianceRate(array $retentionStatus): float
    {
        if (empty($retentionStatus)) {
            return 100.0;
        }

        $compliant = array_filter($retentionStatus, fn($item) => $item['compliant']);

        return round((count($compliant) / count($retentionStatus)) * 100, 2);
    }

    /**
     * Purge old audit logs (respecting retention requirements)
     *
     * @param int $dryRun
     * @return array
     */
    public function purgeOldAuditLogs(bool $dryRun = true): array
    {
        $cutoffDate = now()->subYears(7); // Mexican fiscal requirement minimum

        // Get activities that can be purged
        $purgeable = Activity::where('created_at', '<', $cutoffDate)
            ->whereNotIn('description', self::CRITICAL_ACTIONS)
            ->get();

        $count = $purgeable->count();

        if (!$dryRun && $count > 0) {
            Activity::where('created_at', '<', $cutoffDate)
                ->whereNotIn('description', self::CRITICAL_ACTIONS)
                ->delete();
        }

        return [
            'dry_run' => $dryRun,
            'cutoff_date' => $cutoffDate->toDateString(),
            'purgeable_count' => $count,
            'action_taken' => $dryRun ? 'none (dry run)' : 'deleted',
        ];
    }
}
