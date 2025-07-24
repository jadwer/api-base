<?php

use Illuminate\Support\Facades\Route;
use Modules\User\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Auth;

/*
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('users', UserController::class)->names('user');
});
*/

Route::middleware('auth:sanctum')
    ->prefix('v1')
    ->group(function () {
        Route::get('/profile', function () {
            $user = Auth::user();
            
            // Cargar roles y permisos del usuario
            $user->load(['roles', 'permissions', 'roles.permissions']);

            return response()->json([
                'data' => [
                    'type'       => 'users',
                    'id'         => (string) $user->id,
                    'attributes' => [
                        'name'        => $user->name,
                        'email'       => $user->email,
                        'status'      => $user->status,
                        'role'        => $user->getRoleNames()->first(), // Rol principal
                        'roles'       => $user->roles->map(function ($role) {
                            return [
                                'id'   => $role->id,
                                'name' => $role->name,
                                'guard_name' => $role->guard_name,
                                'permissions' => $role->permissions->map(function ($permission) {
                                    return [
                                        'id'   => $permission->id,
                                        'name' => $permission->name,
                                        'guard_name' => $permission->guard_name,
                                    ];
                                }),
                            ];
                        }),
                        'permissions' => $user->getAllPermissions()->map(function ($permission) {
                            return [
                                'id'   => $permission->id,
                                'name' => $permission->name,
                                'guard_name' => $permission->guard_name,
                            ];
                        }),
                        'created_at'  => $user->created_at,
                        'updated_at'  => $user->updated_at,
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

