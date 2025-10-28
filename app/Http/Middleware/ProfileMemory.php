<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * ProfileMemory Middleware
 *
 * Tracks memory usage for each request and logs high-memory operations.
 *
 * Usage:
 *   Route::middleware('profile.memory')->group(function () { ... });
 *
 * Features:
 * - Tracks memory usage per request
 * - Logs slow/heavy requests
 * - Adds memory headers to response
 * - Warns about high memory usage
 */
class ProfileMemory
{
    /**
     * Memory threshold for logging (in MB)
     */
    protected int $warningThreshold = 50;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Record initial memory
        $startMemory = memory_get_usage(true);
        $startPeak = memory_get_peak_usage(true);
        $startTime = microtime(true);

        // Execute request
        $response = $next($request);

        // Calculate memory usage
        $endMemory = memory_get_usage(true);
        $endPeak = memory_get_peak_usage(true);
        $endTime = microtime(true);

        $memoryUsed = $endMemory - $startMemory;
        $peakUsed = $endPeak - $startPeak;
        $duration = ($endTime - $startTime) * 1000; // Convert to ms

        // Add headers to response
        $this->addMemoryHeaders($response, $memoryUsed, $peakUsed, $duration);

        // Log if memory usage is high
        if ($memoryUsed > $this->warningThreshold * 1024 * 1024) {
            $this->logHighMemoryUsage($request, $memoryUsed, $peakUsed, $duration);
        }

        return $response;
    }

    /**
     * Add memory usage headers to response
     */
    protected function addMemoryHeaders(Response $response, int $used, int $peak, float $duration): void
    {
        // Only add in non-production or when explicitly enabled
        if (config('app.debug') || config('app.profile_memory', false)) {
            $response->headers->set('X-Memory-Used', $this->formatBytes($used));
            $response->headers->set('X-Memory-Peak', $this->formatBytes($peak));
            $response->headers->set('X-Memory-Duration', number_format($duration, 2) . 'ms');
            $response->headers->set('X-Memory-Current', $this->formatBytes(memory_get_usage(true)));
        }
    }

    /**
     * Log high memory usage
     */
    protected function logHighMemoryUsage(Request $request, int $used, int $peak, float $duration): void
    {
        Log::warning('High memory usage detected', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'memory_used' => $this->formatBytes($used),
            'memory_peak' => $this->formatBytes($peak),
            'duration_ms' => number_format($duration, 2),
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
        ]);
    }

    /**
     * Format bytes to human readable
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
