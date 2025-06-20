<?php

namespace Modules\User\JsonApi\V1\Users;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;
use Illuminate\Support\Facades\Gate;


class UserAuthorizer implements Authorizer
{
    public function index(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('users.index') ?? false;
    }

    public function store(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();

        if (!$user) return false;

        if (! $user->can('users.create')) {
            return Response::deny('No tienes permiso para crear usuarios.');
        }

        return Response::allow();
    }

    public function show(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('users.view') ?? false;
    }

    public function update(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('users.update') ?? false;
    }

    public function destroy(Request $request, object $model): bool|Response
    {
        $user = $request->user();

        if (is_null($user)) {
            return false;
        }

        $permission = Gate::forUser($user)->inspect('users.delete');

        if (!$permission->allowed()) {
            return $permission;
        }

        // Reglas de jerarquía de roles
        if ($model->hasRole('god')) {
            // Solo otro god puede eliminar a un god
            return $user->hasRole('god')
                ? Response::allow()
                : Response::deny('No puedes eliminar a un usuario god.');
        }

        if ($model->hasRole('admin') && $user->hasRole('tech')) {
            return Response::deny('Un técnico no puede eliminar a un administrador.');
        }

        return Response::allow();
    }

    public function showRelated(Request $request, object $model, string $fieldName): bool|Response
    {
        return true;
    }

    public function showRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return true;
    }

    public function updateRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return true;
    }

    public function attachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return true;
    }

    public function detachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return true;
    }
}
