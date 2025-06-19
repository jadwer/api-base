<?php

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