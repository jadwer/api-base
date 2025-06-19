<?php

namespace Modules\User\JsonApi\V1\Users;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class UserAuthorizer implements Authorizer
{
    public function index(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('users.index') ?? false;
    }

    public function store(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('users.create') ?? false;
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
        return $request->user()?->can('users.delete') ?? false;
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
