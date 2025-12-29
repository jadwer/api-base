<?php

namespace Modules\SystemHealth\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\SystemHealth\Services\SystemHealthService;

class SystemHealthController extends Controller
{
    protected SystemHealthService $healthService;

    public function __construct(SystemHealthService $healthService)
    {
        $this->healthService = $healthService;
    }

    /**
     * Get complete system health status
     *
     * GET /api/v1/system-health
     */
    public function index(): JsonResponse
    {
        $health = $this->healthService->getHealthStatus();

        $httpStatus = match ($health['status']) {
            'critical' => 503,
            'warning' => 200,
            default => 200,
        };

        return response()->json([
            'data' => [
                'type' => 'system-health',
                'id' => 'current',
                'attributes' => $health,
            ],
        ], $httpStatus);
    }

    /**
     * Simple health check for uptime monitoring
     *
     * GET /api/v1/system-health/ping
     */
    public function ping(): JsonResponse
    {
        $health = $this->healthService->getSimpleHealthCheck();

        return response()->json([
            'data' => [
                'type' => 'health-check',
                'id' => 'ping',
                'attributes' => $health,
            ],
        ], $health['status'] === 'ok' ? 200 : 503);
    }

    /**
     * Get database status and metrics
     *
     * GET /api/v1/system-health/database
     */
    public function database(): JsonResponse
    {
        return response()->json([
            'data' => [
                'type' => 'database-health',
                'id' => 'current',
                'attributes' => [
                    'check' => $this->healthService->checkDatabase(),
                    'metrics' => $this->healthService->getDatabaseMetrics(),
                ],
            ],
        ]);
    }

    /**
     * Get storage/disk status
     *
     * GET /api/v1/system-health/storage
     */
    public function storage(): JsonResponse
    {
        return response()->json([
            'data' => [
                'type' => 'storage-health',
                'id' => 'current',
                'attributes' => $this->healthService->checkStorage(),
            ],
        ]);
    }

    /**
     * Get queue status
     *
     * GET /api/v1/system-health/queue
     */
    public function queue(): JsonResponse
    {
        return response()->json([
            'data' => [
                'type' => 'queue-health',
                'id' => 'current',
                'attributes' => $this->healthService->checkQueue(),
            ],
        ]);
    }

    /**
     * Get recent errors from Telescope
     *
     * GET /api/v1/system-health/errors
     */
    public function errors(): JsonResponse
    {
        return response()->json([
            'data' => [
                'type' => 'error-report',
                'id' => 'current',
                'attributes' => $this->healthService->getRecentErrors(),
            ],
        ]);
    }

    /**
     * Get application metrics
     *
     * GET /api/v1/system-health/metrics
     */
    public function metrics(): JsonResponse
    {
        return response()->json([
            'data' => [
                'type' => 'application-metrics',
                'id' => 'current',
                'attributes' => $this->healthService->getApplicationMetrics(),
            ],
        ]);
    }
}
