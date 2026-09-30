<?php

namespace Modules\User\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\User\Models\User;
use Modules\User\Support\UserAccess;

/**
 * GET/PUT /api/v1/users/{id}/access: roles de sistema, plantilla y permisos
 * directos de un usuario (ver UserAccess). Endpoint custom, no JSON:API.
 */
class UserAccessController extends Controller
{
    public function show(Request $request, int $id): JsonResponse
    {
        $actor = $request->user('sanctum');
        if (! $actor) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        if (! $actor->can('users.show') && ! $actor->can('users.update')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $user = User::findOrFail($id);

        return response()->json(['data' => UserAccess::describe($user)]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $actor = $request->user('sanctum');
        if (! $actor) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        if (! $actor->can('users.update')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'systemRoles' => ['present', 'array'],
            'systemRoles.*' => ['string'],
            'template' => ['nullable', 'string', 'max:100'],
            'permissionIds' => ['present', 'array'],
            'permissionIds.*' => ['integer'],
        ]);

        $user = User::findOrFail($id);
        $user = UserAccess::apply($user, $data['systemRoles'], $data['template'] ?? null, $data['permissionIds'], $actor);

        return response()->json(['data' => UserAccess::describe($user)]);
    }
}
