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
                        'avatar'      => \Modules\User\Http\Controllers\Api\V1\ProfileController::avatarValue($user->avatar),
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
                        // Multi-sucursal: default de los formularios y filtros.
                        'branch_id'   => $user->branch_id ? (string) $user->branch_id : null,
                        // null = sin restriccion (god/admin).
                        'branch_ids'  => ($ids = $user->accessibleBranchIds()) === null ? null : array_map('strval', $ids),
                        'created_at'  => $user->created_at,
                        'updated_at'  => $user->updated_at,
                    ],
                ],
            ]);
        })->name('v1.profile.show');

        // Permisos por usuario (rol = plantilla, 2026-09-24)
        Route::get('users/{id}/access', [\Modules\User\Http\Controllers\Api\V1\UserAccessController::class, 'show'])
            ->whereNumber('id')
            ->name('users.access.show');
        Route::put('users/{id}/access', [\Modules\User\Http\Controllers\Api\V1\UserAccessController::class, 'update'])
            ->whereNumber('id')
            ->name('users.access.update');

        Route::post('users/{id}/restore', [UserController::class, 'restore'])
            ->whereNumber('id')
            ->name('users.restore');

        // Perfil propio (2026-09-30): nombre validado (bug H3) y avatar.
        Route::patch('profile', [\Modules\User\Http\Controllers\Api\V1\ProfileController::class, 'update'])
            ->name('v1.profile.update');
        Route::post('profile/avatar', [\Modules\User\Http\Controllers\Api\V1\ProfileController::class, 'updateAvatar'])
            ->name('v1.profile.avatar.update');
        Route::delete('profile/avatar', [\Modules\User\Http\Controllers\Api\V1\ProfileController::class, 'deleteAvatar'])
            ->name('v1.profile.avatar.delete');

    });

