<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SecureHeaders Middleware
 *
 * Adds security headers to all responses to protect against common attacks:
 * - XSS (Cross-Site Scripting)
 * - Clickjacking
 * - MIME sniffing
 * - Information leakage
 *
 * Headers Applied:
 * - X-Content-Type-Options: nosniff
 * - X-Frame-Options: DENY
 * - X-XSS-Protection: 1; mode=block
 * - Referrer-Policy: strict-origin-when-cross-origin
 * - Content-Security-Policy: default-src 'self'
 * - Permissions-Policy: camera=(), microphone=(), geolocation=()
 *
 * Usage:
 *   Automatically applied to all routes via global middleware
 */
class SecureHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        return $this->addSecurityHeaders($response);
    }

    /**
     * Add security headers to response
     */
    protected function addSecurityHeaders(Response $response): Response
    {
        $headers = [
            // Prevent MIME type sniffing
            'X-Content-Type-Options' => 'nosniff',

            // Prevent clickjacking attacks
            'X-Frame-Options' => 'DENY',

            // Enable browser XSS protection (legacy, but still useful)
            'X-XSS-Protection' => '1; mode=block',

            // Control referrer information
            'Referrer-Policy' => 'strict-origin-when-cross-origin',

            // Remove server information leakage
            'X-Powered-By' => 'PHP',

            // Strict Transport Security (HTTPS only)
            // Enabled only in production with HTTPS
            // 'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',

            // Content Security Policy for API
            // Restrictive policy since this is an API, not serving HTML
            'Content-Security-Policy' => implode('; ', [
                "default-src 'none'",
                "frame-ancestors 'none'",
                "base-uri 'none'",
            ]),

            // Permissions Policy (formerly Feature Policy)
            // Disable all browser features for API
            'Permissions-Policy' => implode(', ', [
                'camera=()',
                'microphone=()',
                'geolocation=()',
                'payment=()',
                'usb=()',
                'magnetometer=()',
                'accelerometer=()',
                'gyroscope=()',
            ]),

            // CORS headers (if not handled by Laravel CORS middleware)
            // These should match your config/cors.php
            // 'Access-Control-Allow-Origin' => config('app.frontend_url', '*'),
            // 'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
            // 'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
        ];

        // Add HSTS only in production with HTTPS
        if (app()->environment('production') && request()->secure()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains; preload';
        }

        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }
}
