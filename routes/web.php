<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'API Base Laravel 12 – Acceso permitido solo vía /api',
        'status' => 'ok'
    ]);
});
