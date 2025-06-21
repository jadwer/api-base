<?php

use Illuminate\Support\Facades\Route;
use Modules\User\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Auth;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('users', UserController::class)->names('user');
});

Route::middleware('auth:sanctum')
    ->prefix('v1')
    ->group(function () {
        Route::get('/profile', function () {
            $user = Auth::user();

            return response()->json([
                'data' => [
                    'type'       => 'users',
                    'id'         => (string) $user->id,
                    'attributes' => [
                        'name'   => $user->name,
                        'email'  => $user->email,
                    ],
                ],
            ]);
        })->name('v1.profile.show');

        Route::patch('profile', function (\Illuminate\Http\Request $request) {
            $user = Auth::user();
            $data = $request->only(['name', 'email', 'status']); // o los campos que permitas actualizar
            $user->update($data);
            return response()->json([
                'data' => [
                    'type'       => 'users',
                    'id'         => (string) $user->id,
                    'attributes' => [
                        'name'   => $user->name,
                        'email'  => $user->email,
                    ],
                ],
            ]);
        })->name('v1.profile.update');

    });

