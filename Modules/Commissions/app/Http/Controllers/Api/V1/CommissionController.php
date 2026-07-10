<?php

namespace Modules\Commissions\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;
use Modules\Commissions\Models\Commission;

/**
 * CommissionController
 *
 * JSON:API index/show plus custom endpoints:
 * - GET  /api/v1/commissions/by-period?start&end&user_id&status
 * - GET  /api/v1/commissions/by-employee?start&end
 * - POST /api/v1/commissions/{id}/mark-paid {payment_reference}
 * - POST /api/v1/commissions/pay-batch {ids[], payment_reference}
 */
class CommissionController extends JsonApiController
{
    /**
     * Commissions in a period (payout cut). Period filters on earned_at.
     */
    public function byPeriod(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        if (!$user->can('commissions.index')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date', 'after_or_equal:start'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', 'in:pending,earned,paid,cancelled'],
        ]);

        $query = Commission::query()->with(['user:id,name,email']);

        if (!empty($validated['start'])) {
            $query->where('earned_at', '>=', $validated['start']);
        }
        if (!empty($validated['end'])) {
            $query->where('earned_at', '<=', $validated['end'] . ' 23:59:59');
        }
        if (!empty($validated['user_id'])) {
            $query->where('user_id', $validated['user_id']);
        }
        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $commissions = $query->orderBy('earned_at')->get();

        return response()->json([
            'data' => $commissions->map(fn (Commission $c) => $this->transformCommission($c)),
            'meta' => [
                'count' => $commissions->count(),
                'total_commission_amount' => round((float) $commissions->sum('commission_amount'), 2),
            ],
        ]);
    }

    /**
     * Aggregate per salesperson for a period (earned_at based).
     */
    public function byEmployee(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        if (!$user->can('commissions.index')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date', 'after_or_equal:start'],
        ]);

        $query = Commission::query()
            ->whereIn('status', [Commission::STATUS_EARNED, Commission::STATUS_PAID]);

        if (!empty($validated['start'])) {
            $query->where('earned_at', '>=', $validated['start']);
        }
        if (!empty($validated['end'])) {
            $query->where('earned_at', '<=', $validated['end'] . ' 23:59:59');
        }

        $rows = $query
            ->select('user_id')
            ->selectRaw('COUNT(*) as commissions_count')
            ->selectRaw('SUM(base_amount) as total_base_amount')
            ->selectRaw('SUM(commission_amount) as total_commission_amount')
            ->selectRaw("SUM(CASE WHEN status = 'earned' THEN commission_amount ELSE 0 END) as earned_amount")
            ->selectRaw("SUM(CASE WHEN status = 'paid' THEN commission_amount ELSE 0 END) as paid_amount")
            ->groupBy('user_id')
            ->get();

        $users = \Modules\User\Models\User::whereIn('id', $rows->pluck('user_id'))
            ->get(['id', 'name', 'email'])
            ->keyBy('id');

        return response()->json([
            'data' => $rows->map(fn ($row) => [
                'user_id' => (int) $row->user_id,
                'user_name' => $users[$row->user_id]->name ?? null,
                'user_email' => $users[$row->user_id]->email ?? null,
                'commissions_count' => (int) $row->commissions_count,
                'total_base_amount' => round((float) $row->total_base_amount, 2),
                'total_commission_amount' => round((float) $row->total_commission_amount, 2),
                'earned_amount' => round((float) $row->earned_amount, 2),
                'paid_amount' => round((float) $row->paid_amount, 2),
            ])->values(),
        ]);
    }

    /**
     * Mark a single earned commission as paid.
     */
    public function markPaid(Request $request, int $id): JsonResponse
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        if (!$user->can('commissions.pay')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'payment_reference' => ['required', 'string', 'max:255'],
        ]);

        $commission = Commission::find($id);

        if (!$commission) {
            return response()->json(['error' => 'Commission not found'], 404);
        }

        if (!$commission->isEarned()) {
            return response()->json([
                'error' => "Only earned commissions can be marked as paid (current status: {$commission->status})",
            ], 422);
        }

        $commission->update([
            'status' => Commission::STATUS_PAID,
            'paid_at' => now(),
            'payment_reference' => $validated['payment_reference'],
        ]);

        return response()->json([
            'data' => $this->transformCommission($commission->fresh()),
            'message' => 'Commission marked as paid',
        ]);
    }

    /**
     * Mark a batch of earned commissions as paid, transactionally: if any
     * commission is not in earned status, nothing is updated (422).
     */
    public function payBatch(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        if (!$user->can('commissions.pay')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'payment_reference' => ['required', 'string', 'max:255'],
        ]);

        try {
            $paid = DB::transaction(function () use ($validated) {
                $commissions = Commission::whereIn('id', $validated['ids'])
                    ->lockForUpdate()
                    ->get();

                $foundIds = $commissions->pluck('id')->all();
                $missing = array_values(array_diff($validated['ids'], $foundIds));

                if (!empty($missing)) {
                    throw new \DomainException('Commissions not found: ' . implode(', ', $missing));
                }

                $notEarned = $commissions->filter(fn (Commission $c) => !$c->isEarned());

                if ($notEarned->isNotEmpty()) {
                    throw new \DomainException(
                        'Batch aborted, commissions not in earned status: '
                        . $notEarned->map(fn ($c) => "#{$c->id} ({$c->status})")->implode(', ')
                    );
                }

                $now = now();

                foreach ($commissions as $commission) {
                    $commission->update([
                        'status' => Commission::STATUS_PAID,
                        'paid_at' => $now,
                        'payment_reference' => $validated['payment_reference'],
                    ]);
                }

                return $commissions;
            });
        } catch (\DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => $paid->map(fn (Commission $c) => $this->transformCommission($c->fresh())),
            'message' => 'Commissions marked as paid',
        ]);
    }

    /**
     * Flat transform for custom (non JSON:API) responses.
     */
    private function transformCommission(Commission $commission): array
    {
        return [
            'id' => $commission->id,
            'sales_order_id' => $commission->sales_order_id,
            'ar_invoice_id' => $commission->ar_invoice_id,
            'user_id' => $commission->user_id,
            'user_name' => $commission->relationLoaded('user') ? $commission->user?->name : null,
            'contact_id' => $commission->contact_id,
            'base_amount' => $commission->base_amount,
            'commission_pct' => $commission->commission_pct,
            'commission_amount' => $commission->commission_amount,
            'status' => $commission->status,
            'earned_at' => $commission->earned_at?->toIso8601String(),
            'paid_at' => $commission->paid_at?->toIso8601String(),
            'payment_reference' => $commission->payment_reference,
        ];
    }
}
