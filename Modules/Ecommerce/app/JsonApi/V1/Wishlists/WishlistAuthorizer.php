<?php

namespace Modules\Ecommerce\JsonApi\V1\Wishlists;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class WishlistAuthorizer implements Authorizer
{
    public function index(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('ecommerce.wishlists.index') ?? false;
    }

    public function store(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('ecommerce.wishlists.store') ?? false;
    }

    public function show(Request $request, object $model): bool|Response
    {
        if ($model->is_public) {
            return true;
        }
        $user = $request->user();
        if (!$user) {
            return false;
        }
        if ($user->can('ecommerce.wishlists.show')) {
            return true;
        }
        return isset($model->user_id) && $model->user_id === $user->id;
    }

    public function update(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }
        if ($user->can('ecommerce.wishlists.update')) {
            return true;
        }
        return isset($model->user_id) && $model->user_id === $user->id;
    }

    public function destroy(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }
        if ($user->can('ecommerce.wishlists.destroy')) {
            return true;
        }
        return isset($model->user_id) && $model->user_id === $user->id;
    }

    public function showRelated(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->show($request, $model);
    }

    public function showRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->show($request, $model);
    }

    public function updateRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->update($request, $model);
    }

    public function attachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->update($request, $model);
    }

    public function detachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->update($request, $model);
    }
}
