<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\Api\V1\AuthController;
use Modules\Auth\Http\Controllers\Api\V1\EmailVerificationController;
use Modules\Auth\Http\Controllers\Api\V1\ForgotPasswordController;
use Modules\Auth\Http\Controllers\Api\V1\ResetPasswordController;
use Modules\Auth\Http\Controllers\ProfilePasswordController;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('login.throttle')
        ->name('auth.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');

        Route::post('/email/verification-notification', [EmailVerificationController::class, 'sendVerificationEmail'])
            ->middleware('throttle:3,1')
            ->name('auth.verification.send');
    });

    // Public (user clicks link from email)
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->whereNumber('id')
        ->name('auth.verification.verify');

    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:5,1')
        ->name('auth.register');

    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('auth.forgot-password');

    Route::post('/reset-password', [ResetPasswordController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('auth.reset-password');
});

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::patch('profile/password', [ProfilePasswordController::class, 'update']);
});