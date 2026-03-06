<?php

namespace Modules\User\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;
use Modules\User\Models\User;

class UserController extends JsonApiController
{
    /**
     * Restore a soft-deleted user.
     *
     * POST /api/v1/users/{id}/restore
     */
    public function restore(Request $request, int $id): JsonResponse
    {
        $user = $request->user('sanctum');

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (!$user->can('users.store')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $trashedUser = User::onlyTrashed()->findOrFail($id);
        $trashedUser->restore();

        return response()->json([
            'message' => 'Usuario restaurado exitosamente',
            'data' => [
                'id' => $trashedUser->id,
                'name' => $trashedUser->name,
                'email' => $trashedUser->email,
            ],
        ]);
    }
}
