<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * DemoController - endpoints for the public product demo instance.
 *
 * GET  /api/v1/demo/status  (public)          -> demo flag + next scheduled reset
 * POST /api/v1/demo/reset   (auth + throttle) -> on-demand demo:reset --force
 *
 * Both are safe on non-demo instances: status just reports demo_mode=false
 * and reset returns 403 (plus the command itself refuses without demo mode).
 */
class DemoController extends Controller
{
    /**
     * Public status endpoint: is this a demo instance, and when is the
     * next scheduled reset (Monday 03:00, matching routes/console.php).
     */
    public function status(): JsonResponse
    {
        $demoMode = (bool) config('app.demo_mode');

        return response()->json([
            'demo_mode' => $demoMode,
            'next_scheduled_reset' => $demoMode ? $this->nextScheduledReset()->toIso8601String() : null,
        ]);
    }

    /**
     * On-demand reset. Requires an authenticated user, is heavily throttled
     * (1 request / 5 min at the route level) and refuses when demo mode is off.
     */
    public function reset(Request $request): JsonResponse
    {
        // Manual auth check (template pattern for custom controllers: the
        // auth:sanctum alias maps to EnsureFrontendRequestsAreStateful and
        // does not reject guests by itself).
        $user = $request->user('sanctum');
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (config('app.demo_mode') !== true) {
            return response()->json([
                'error' => 'Demo mode is not enabled on this instance.',
            ], 403);
        }

        $exitCode = Artisan::call('demo:reset', ['--force' => true]);

        if ($exitCode !== 0) {
            return response()->json([
                'error' => 'Demo reset failed. Check the application logs.',
            ], 500);
        }

        return response()->json([
            'message' => 'Demo database was reset successfully.',
            'reset_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Next Monday 03:00 (server time), including today if it is Monday
     * and 03:00 has not passed yet. Mirrors weeklyOn(1, '03:00').
     */
    private function nextScheduledReset(): Carbon
    {
        $next = now()->setTime(3, 0, 0);

        if (!$next->isMonday() || $next->lessThanOrEqualTo(now())) {
            $next = $next->next(Carbon::MONDAY)->setTime(3, 0, 0);
        }

        return $next;
    }
}
