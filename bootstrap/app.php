<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global middleware (applied to all routes)
        $middleware->append(\App\Http\Middleware\SecureHeaders::class);

        // Route middleware aliases
        $middleware->alias([
            'auth:sanctum' => \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'cache.jsonapi' => \App\Http\Middleware\CacheJsonApiResponse::class,
            'api.ratelimit' => \App\Http\Middleware\ApiRateLimiter::class,
            'login.throttle' => \App\Http\Middleware\LoginThrottler::class,
            'profile.memory' => \App\Http\Middleware\ProfileMemory::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontReport(
            \LaravelJsonApi\Core\Exceptions\JsonApiException::class,
        );
        $exceptions->render(
            \LaravelJsonApi\Exceptions\ExceptionParser::renderer(),
        );
    })
    ->create();
