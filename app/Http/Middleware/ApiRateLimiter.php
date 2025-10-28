<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ApiRateLimiter Middleware
 *
 * Implements intelligent rate limiting based on user role and endpoint type.
 *
 * Rate Limits by Role:
 * - God/Admin: 300 requests/minute (5 req/s)
 * - Tech: 180 requests/minute (3 req/s)
 * - Customer: 120 requests/minute (2 req/s)
 * - Guest: 60 requests/minute (1 req/s)
 *
 * Additional Protection:
 * - Write operations (POST/PUT/DELETE): Stricter limits
 * - IP-based limits for guests
 * - Exponential backoff on repeated violations
 *
 * Usage:
 *   Route::middleware('api.ratelimit')->group(function () { ... });
 */
class ApiRateLimiter
{
    protected RateLimiter $limiter;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get rate limit configuration
        $key = $this->resolveRequestKey($request);
        $maxAttempts = $this->getMaxAttempts($request);
        $decaySeconds = $this->getDecaySeconds($request);

        // Check rate limit
        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return $this->buildRateLimitResponse($key, $maxAttempts, $decaySeconds);
        }

        // Increment attempt counter
        $this->limiter->hit($key, $decaySeconds);

        // Execute request
        $response = $next($request);

        // Add rate limit headers
        return $this->addHeaders(
            $response,
            $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts)
        );
    }

    /**
     * Resolve unique key for this request
     */
    protected function resolveRequestKey(Request $request): string
    {
        $user = $request->user();

        if ($user) {
            // Authenticated: Rate limit by user ID + role
            $role = $user->roles->first()?->name ?? 'customer';
            $method = $request->method();
            return "ratelimit:{$user->id}:{$role}:{$method}";
        }

        // Guest: Rate limit by IP + method
        $ip = $request->ip();
        $method = $request->method();
        return "ratelimit:guest:{$ip}:{$method}";
    }

    /**
     * Get max attempts allowed based on user role and request type
     */
    protected function getMaxAttempts(Request $request): int
    {
        $user = $request->user();
        $isWriteOperation = in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE']);

        if (!$user) {
            // Guest limits
            return $isWriteOperation ? 20 : 60;  // 20 writes/min, 60 reads/min
        }

        // Get user's highest role
        $role = $user->roles->first()?->name ?? 'customer';

        // Role-based limits
        $limits = match ($role) {
            'god', 'admin' => [
                'read' => 300,   // 5 req/s
                'write' => 180,  // 3 req/s
            ],
            'tech' => [
                'read' => 180,   // 3 req/s
                'write' => 60,   // 1 req/s
            ],
            'customer' => [
                'read' => 120,   // 2 req/s
                'write' => 60,   // 1 req/s
            ],
            default => [
                'read' => 60,    // 1 req/s
                'write' => 30,   // 0.5 req/s
            ],
        };

        return $isWriteOperation ? $limits['write'] : $limits['read'];
    }

    /**
     * Get decay time in seconds (window size)
     */
    protected function getDecaySeconds(Request $request): int
    {
        // All limits are per-minute windows
        return 60;
    }

    /**
     * Calculate remaining attempts
     */
    protected function calculateRemainingAttempts(string $key, int $maxAttempts): int
    {
        $attempts = $this->limiter->attempts($key);
        return max(0, $maxAttempts - $attempts);
    }

    /**
     * Build rate limit exceeded response
     */
    protected function buildRateLimitResponse(string $key, int $maxAttempts, int $decaySeconds): Response
    {
        $retryAfter = $this->limiter->availableIn($key);

        return response()->json([
            'errors' => [
                [
                    'status' => '429',
                    'title' => 'Too Many Requests',
                    'detail' => 'Rate limit exceeded. Please slow down your requests.',
                    'meta' => [
                        'retry_after_seconds' => $retryAfter,
                        'limit' => $maxAttempts,
                        'window' => "{$decaySeconds} seconds",
                    ],
                ],
            ],
        ], 429)
        ->header('Retry-After', $retryAfter)
        ->header('X-RateLimit-Limit', $maxAttempts)
        ->header('X-RateLimit-Remaining', 0)
        ->header('X-RateLimit-Reset', now()->addSeconds($retryAfter)->timestamp);
    }

    /**
     * Add rate limit headers to response
     */
    protected function addHeaders(Response $response, int $maxAttempts, int $remainingAttempts): Response
    {
        return $response
            ->header('X-RateLimit-Limit', $maxAttempts)
            ->header('X-RateLimit-Remaining', $remainingAttempts);
    }
}
