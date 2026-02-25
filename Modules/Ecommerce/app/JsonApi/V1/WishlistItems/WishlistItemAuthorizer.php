<?php

namespace Modules\Ecommerce\JsonApi\V1\WishlistItems;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class WishlistItemAuthorizer implements Authorizer
{
    public function index(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('ecommerce.wishlist-items.index') ?? false;
    }

    public function store(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('ecommerce.wishlist-items.store') ?? false;
    }

    public function show(Request $request, object $model): bool|Response
    {
        $wishlist = $model->wishlist;
        if ($wishlist && $wishlist->is_public) {
            return true;
        }
        $user = $request->user();
        if (!$user) {
            return false;
        }
        if ($user->can('ecommerce.wishlist-items.show')) {
            return true;
        }
        return $wishlist && isset($wishlist->user_id) && $wishlist->user_id === $user->id;
    }

    public function update(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }
        if ($user->can('ecommerce.wishlist-items.update')) {
            return true;
        }
        $wishlist = $model->wishlist;
        return $wishlist && isset($wishlist->user_id) && $wishlist->user_id === $user->id;
    }

    public function destroy(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }
        if ($user->can('ecommerce.wishlist-items.destroy')) {
            return true;
        }
        $wishlist = $model->wishlist;
        return $wishlist && isset($wishlist->user_id) && $wishlist->user_id === $user->id;
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
