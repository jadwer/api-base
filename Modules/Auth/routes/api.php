<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\Api\V1\AuthController;
use Modules\Auth\Http\Controllers\ProfilePasswordController;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
    });

    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
});

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::patch('profile/password', [ProfilePasswordController::class, 'update']);
});