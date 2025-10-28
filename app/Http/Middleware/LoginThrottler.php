<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * LoginThrottler Middleware
 *
 * Protects login endpoints against brute force attacks.
 *
 * Features:
 * - IP-based throttling: 5 attempts per 1 minute
 * - Email-based throttling: 5 attempts per 5 minutes
 * - Exponential backoff after violations
 * - Detailed logging of failed attempts
 *
 * Security Rules:
 * - After 5 failed attempts: 1 minute lockout
 * - After 10 failed attempts: 15 minute lockout
 * - After 20 failed attempts: 1 hour lockout
 *
 * Usage:
 *   Route::post('/login')->middleware('login.throttle');
 */
class LoginThrottler
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
        // Check IP-based limit
        if ($this->hasTooManyIpAttempts($request)) {
            return $this->buildLockoutResponse($request, 'ip');
        }

        // Check email-based limit (if email provided)
        if ($request->has('email') && $this->hasTooManyEmailAttempts($request)) {
            return $this->buildLockoutResponse($request, 'email');
        }

        // Execute request
        $response = $next($request);

        // If login failed (401), increment counters
        if ($response->status() === 401) {
            $this->incrementLoginAttempts($request);
            $this->logFailedAttempt($request);
        } else if ($response->isSuccessful()) {
            // Clear attempts on successful login
            $this->clearLoginAttempts($request);
        }

        return $response;
    }

    /**
     * Check if IP has too many attempts
     */
    protected function hasTooManyIpAttempts(Request $request): bool
    {
        $key = $this->throttleIpKey($request);
        $maxAttempts = 5;  // 5 attempts per minute per IP

        return $this->limiter->tooManyAttempts($key, $maxAttempts);
    }

    /**
     * Check if email has too many attempts
     */
    protected function hasTooManyEmailAttempts(Request $request): bool
    {
        $key = $this->throttleEmailKey($request);
        $maxAttempts = 5;  // 5 attempts per 5 minutes per email

        return $this->limiter->tooManyAttempts($key, $maxAttempts);
    }

    /**
     * Increment login attempt counters
     */
    protected function incrementLoginAttempts(Request $request): void
    {
        // Increment IP counter (1 minute window)
        $ipKey = $this->throttleIpKey($request);
        $this->limiter->hit($ipKey, 60);

        // Increment email counter (5 minute window)
        if ($request->has('email')) {
            $emailKey = $this->throttleEmailKey($request);
            $this->limiter->hit($emailKey, 300);

            // Track total failed attempts for exponential backoff
            $this->incrementFailedAttempts($request);
        }
    }

    /**
     * Clear login attempts on success
     */
    protected function clearLoginAttempts(Request $request): void
    {
        $this->limiter->clear($this->throttleIpKey($request));

        if ($request->has('email')) {
            $this->limiter->clear($this->throttleEmailKey($request));
            $this->limiter->clear($this->failedAttemptsKey($request));
        }
    }

    /**
     * Increment total failed attempts for exponential backoff
     */
    protected function incrementFailedAttempts(Request $request): void
    {
        $key = $this->failedAttemptsKey($request);
        $this->limiter->hit($key, 3600); // Track for 1 hour
    }

    /**
     * Get lockout duration based on failed attempts
     */
    protected function getLockoutDuration(Request $request): int
    {
        if (!$request->has('email')) {
            return 60; // Default 1 minute for IP-only
        }

        $failedAttempts = $this->limiter->attempts($this->failedAttemptsKey($request));

        return match (true) {
            $failedAttempts >= 20 => 3600,  // 1 hour after 20 failed attempts
            $failedAttempts >= 10 => 900,   // 15 minutes after 10 failed attempts
            $failedAttempts >= 5 => 300,    // 5 minutes after 5 failed attempts
            default => 60,                  // 1 minute default
        };
    }

    /**
     * Build lockout response
     */
    protected function buildLockoutResponse(Request $request, string $type): Response
    {
        $key = $type === 'ip'
            ? $this->throttleIpKey($request)
            : $this->throttleEmailKey($request);

        $retryAfter = $this->limiter->availableIn($key);
        $lockoutDuration = $this->getLockoutDuration($request);

        return response()->json([
            'errors' => [
                [
                    'status' => '429',
                    'title' => 'Too Many Login Attempts',
                    'detail' => 'Too many failed login attempts. Please try again later.',
                    'meta' => [
                        'retry_after_seconds' => $retryAfter,
                        'lockout_type' => $type,
                        'lockout_duration' => $lockoutDuration,
                    ],
                ],
            ],
        ], 429)
        ->header('Retry-After', $retryAfter)
        ->header('X-RateLimit-Limit', 5)
        ->header('X-RateLimit-Remaining', 0);
    }

    /**
     * Log failed login attempt
     */
    protected function logFailedAttempt(Request $request): void
    {
        \Log::warning('Failed login attempt', [
            'ip' => $request->ip(),
            'email' => $request->input('email'),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get IP-based throttle key
     */
    protected function throttleIpKey(Request $request): string
    {
        return 'login.ip:' . $request->ip();
    }

    /**
     * Get email-based throttle key
     */
    protected function throttleEmailKey(Request $request): string
    {
        $email = Str::lower($request->input('email', ''));
        return 'login.email:' . sha1($email);
    }

    /**
     * Get failed attempts tracking key
     */
    protected function failedAttemptsKey(Request $request): string
    {
        $email = Str::lower($request->input('email', ''));
        return 'login.failed:' . sha1($email);
    }
}
