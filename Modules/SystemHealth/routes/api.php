<?php

use Illuminate\Support\Facades\Route;
use Modules\SystemHealth\Http\Controllers\Api\V1\SystemHealthController;

/*
|--------------------------------------------------------------------------
| SystemHealth API Routes
|--------------------------------------------------------------------------
|
| These routes provide system health monitoring endpoints.
| Most endpoints require authentication and god/admin role.
| The /ping endpoint is public for uptime monitoring.
|
*/

Route::prefix('v1')->group(function () {
    // Public endpoint for uptime monitoring (no auth required)
    Route::get('system-health/ping', [SystemHealthController::class, 'ping'])
        ->name('system-health.ping');

    // Protected endpoints (require authentication and permissions)
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('system-health', [SystemHealthController::class, 'index'])
            ->middleware('can:system-health.index')
            ->name('system-health.index');

        Route::get('system-health/database', [SystemHealthController::class, 'database'])
            ->middleware('can:system-health.database')
            ->name('system-health.database');

        Route::get('system-health/storage', [SystemHealthController::class, 'storage'])
            ->middleware('can:system-health.storage')
            ->name('system-health.storage');

        Route::get('system-health/queue', [SystemHealthController::class, 'queue'])
            ->middleware('can:system-health.queue')
            ->name('system-health.queue');

        Route::get('system-health/errors', [SystemHealthController::class, 'errors'])
            ->middleware('can:system-health.errors')
            ->name('system-health.errors');

        Route::get('system-health/metrics', [SystemHealthController::class, 'metrics'])
            ->middleware('can:system-health.metrics')
            ->name('system-health.metrics');
    });
});
