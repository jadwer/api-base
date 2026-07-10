<?php

use App\Http\Controllers\DemoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
*/

Route::get('/', function () {
    $routes = collect(Route::getRoutes())
        ->filter(function ($route) {
            return str_starts_with($route->uri(), 'api/') &&
                   !str_contains($route->uri(), '{'); // opcional: evitar rutas dinámicas
        })
        ->map(function ($route) {
            return [
                'method' => implode('|', $route->methods()),
                'uri' => '/' . $route->uri(),
                'name' => $route->getName(),
                'action' => $route->getActionName(),
            ];
        })
        ->values();

    return response()->json([
        'api' => 'Atomo Soluciones – API Base Laravel 12',
        'version' => 'v1',
        'routes' => $routes,
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Demo mode endpoints (public demo instance, marca blanca).
// status is public; reset requires auth and is throttled to 1 request per 5 minutes.
Route::prefix('v1/demo')->group(function () {
    Route::get('status', [DemoController::class, 'status'])->name('demo.status');

    Route::post('reset', [DemoController::class, 'reset'])
        ->middleware(['auth:sanctum', 'throttle:1,5'])
        ->name('demo.reset');
});