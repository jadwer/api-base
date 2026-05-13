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

        // This is an API-only template (Sanctum tokens, no web login route).
        // Prevent the default Authenticate middleware from trying to redirect
        // guests to a `login` named route that does not exist, which would
        // turn 401 responses into 500 RouteNotFoundException errors.
        $middleware->redirectGuestsTo(fn () => null);

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

        // Render AuthenticationException as JSON:API-compliant 401 instead of
        // letting Laravel attempt a guest redirect (which fails because this
        // template has no `login` named route). Must be registered BEFORE the
        // generic JSON:API renderer so it takes precedence.
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            return response()->json([
                'errors' => [[
                    'status' => '401',
                    'title'  => 'Unauthenticated',
                    'detail' => $e->getMessage() ?: 'Authentication required.',
                ]],
            ], 401, ['Content-Type' => 'application/vnd.api+json']);
        });

        $exceptions->render(
            \LaravelJsonApi\Exceptions\ExceptionParser::renderer(),
        );
    })
    ->create();
