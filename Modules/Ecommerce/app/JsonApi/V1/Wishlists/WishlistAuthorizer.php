<?php

namespace Modules\Ecommerce\JsonApi\V1\Wishlists;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class WishlistAuthorizer implements Authorizer
{
    /**
     * Users can view their own wishlists, admin/god can view all
     */
    public function index(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }

        return $user->can('ecommerce.wishlists.index')
            || $user->hasAnyRole(['god', 'admin', 'tech', 'customer']);
    }

    /**
     * Authenticated users can create wishlists
     */
    public function store(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        return $user && ($user->can('ecommerce.wishlists.store')
            || $user->hasAnyRole(['god', 'admin', 'tech', 'customer']));
    }

    /**
     * Owner, admin, or god can view wishlist. Public wishlists viewable by anyone.
     */
    public function show(Request $request, object $model): bool|Response
    {
        // If wishlist is public, anyone can see it
        if ($model->is_public) {
            return true;
        }

        // For private wishlists, check authorization
        $user = $request->user();
        if (!$user) {
            return Response::deny('Only public wishlists can be viewed without authentication.');
        }

        if ($user->hasAnyRole(['god', 'admin'])) {
            return true;
        }

        if (isset($model->user_id) && $model->user_id === $user->id) {
            return true;
        }

        return Response::deny('You can only view your own wishlists or public wishlists.');
    }

    /**
     * Only owner, admin, or god can update wishlist
     */
    public function update(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }

        if ($user->hasAnyRole(['god', 'admin'])) {
            return true;
        }

        if (isset($model->user_id) && $model->user_id === $user->id) {
            return true;
        }

        return Response::deny('You can only update your own wishlists.');
    }

    /**
     * Only owner, admin, or god can delete wishlist
     */
    public function destroy(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }

        if ($user->hasAnyRole(['god', 'admin'])) {
            return true;
        }

        if (isset($model->user_id) && $model->user_id === $user->id) {
            return true;
        }

        return Response::deny('You can only delete your own wishlists.');
    }

    /**
     * Authorization for viewing related resources
     */
    public function showRelated(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->show($request, $model);
    }

    /**
     * Authorization for viewing relationship data
     */
    public function showRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->show($request, $model);
    }

    /**
     * Authorization for updating relationships
     */
    public function updateRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->update($request, $model);
    }

    /**
     * Authorization for attaching to relationships
     */
    public function attachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->update($request, $model);
    }

    /**
     * Authorization for detaching from relationships
     */
    public function detachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->update($request, $model);
    }
}
