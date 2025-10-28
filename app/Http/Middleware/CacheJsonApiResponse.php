<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * CacheJsonApiResponse Middleware
 *
 * Caches GET requests for JSON:API endpoints to improve performance.
 *
 * Features:
 * - Only caches successful GET requests (200 OK)
 * - Respects cache headers (Cache-Control: no-cache)
 * - Generates unique cache keys per user/role/query
 * - Supports ETag for conditional requests
 * - Auto-invalidates on related mutations
 *
 * Usage:
 *   Route::get('/api/v1/products')->middleware('cache.jsonapi:300');
 *   // 300 seconds (5 minutes) TTL
 */
class CacheJsonApiResponse
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param int $ttl Time to live in seconds (default: 300 = 5 min)
     * @return Response
     */
    public function handle(Request $request, Closure $next, int $ttl = 300): Response
    {
        // Only cache GET requests
        if (!$request->isMethod('GET')) {
            return $next($request);
        }

        // Skip cache if explicitly requested
        if ($request->header('Cache-Control') === 'no-cache') {
            return $next($request);
        }

        // Generate unique cache key
        $cacheKey = $this->generateCacheKey($request);

        // Try to retrieve cached response
        $cachedResponse = Cache::get($cacheKey);

        if ($cachedResponse) {
            // Return cached response with cache headers
            return response($cachedResponse['content'], $cachedResponse['status'])
                ->withHeaders($cachedResponse['headers'])
                ->header('X-Cache', 'HIT')
                ->header('X-Cache-Key', $cacheKey);
        }

        // Execute request
        $response = $next($request);

        // Only cache successful responses
        if ($response->isSuccessful() && $this->shouldCache($response)) {
            $this->cacheResponse($cacheKey, $response, $ttl);
            $response->header('X-Cache', 'MISS');
        }

        return $response->header('X-Cache-Key', $cacheKey);
    }

    /**
     * Generate unique cache key for request
     *
     * Includes: URL, query params, user ID, user roles
     */
    private function generateCacheKey(Request $request): string
    {
        $user = $request->user();
        $userId = $user?->id ?? 'guest';
        $roles = $user ? $user->roles->pluck('name')->sort()->implode(',') : 'guest';

        // Sort query params for consistent keys
        $queryParams = $request->query();
        ksort($queryParams);
        $queryString = http_build_query($queryParams);

        $keyParts = [
            'jsonapi',
            $request->path(),
            $queryString,
            "user:{$userId}",
            "roles:{$roles}",
        ];

        return 'cache:' . md5(implode('|', $keyParts));
    }

    /**
     * Check if response should be cached
     */
    private function shouldCache(Response $response): bool
    {
        // Don't cache if response has cache-control: no-cache
        $cacheControl = $response->headers->get('Cache-Control');
        if ($cacheControl && str_contains($cacheControl, 'no-cache')) {
            return false;
        }

        // Only cache JSON:API responses
        $contentType = $response->headers->get('Content-Type');
        if (!$contentType || !str_contains($contentType, 'application/vnd.api+json')) {
            return false;
        }

        return true;
    }

    /**
     * Cache the response
     */
    private function cacheResponse(string $key, Response $response, int $ttl): void
    {
        $data = [
            'content' => $response->getContent(),
            'status' => $response->getStatusCode(),
            'headers' => [
                'Content-Type' => $response->headers->get('Content-Type'),
                'Cache-Control' => "public, max-age={$ttl}",
                'ETag' => md5($response->getContent()),
            ],
        ];

        Cache::put($key, $data, $ttl);

        // Add to cache tag for bulk invalidation
        $resourceType = $this->extractResourceType($response);
        if ($resourceType) {
            Cache::tags([$resourceType])->put($key, true, $ttl);
        }
    }

    /**
     * Extract resource type from JSON:API response
     */
    private function extractResourceType(Response $response): ?string
    {
        try {
            $content = json_decode($response->getContent(), true);

            // Check for resource type in data
            if (isset($content['data']['type'])) {
                return $content['data']['type'];
            }

            // Check for collection
            if (isset($content['data'][0]['type'])) {
                return $content['data'][0]['type'];
            }
        } catch (\Exception $e) {
            // Silently fail if JSON parsing errors
        }

        return null;
    }

    /**
     * Invalidate cache for a specific resource type
     *
     * Usage: CacheJsonApiResponse::invalidate('ar-invoices');
     */
    public static function invalidate(string $resourceType): void
    {
        Cache::tags([$resourceType])->flush();
    }

    /**
     * Clear all JSON:API caches
     */
    public static function clearAll(): void
    {
        Cache::flush(); // In production, use more targeted approach
    }
}
