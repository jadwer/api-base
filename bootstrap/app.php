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
                    // Regla 9: en espanol (lang/es.json traduce el texto del framework).
                    'title'  => __('Unauthenticated'),
                    'detail' => __($e->getMessage() ?: 'Authentication required.'),
                ]],
            ], 401, ['Content-Type' => 'application/vnd.api+json']);
        });

        // Regla 9: ruta inexistente en el API ("The route ... could not be
        // found." de Symfony). Los 404 de recursos los sigue dando JSON:API.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') && !($e->getPrevious() instanceof \Illuminate\Database\Eloquent\ModelNotFoundException)
                && str_starts_with($e->getMessage(), 'The route ')) {
                return response()->json(['message' => 'La ruta solicitada no existe.'], 404);
            }

            return null;
        });

        $exceptions->render(
            \LaravelJsonApi\Exceptions\ExceptionParser::renderer(),
        );
    })
    ->create();
