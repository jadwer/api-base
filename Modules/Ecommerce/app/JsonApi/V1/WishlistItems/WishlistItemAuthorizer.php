<?php

namespace Modules\Ecommerce\JsonApi\V1\WishlistItems;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class WishlistItemAuthorizer implements Authorizer
{
    /**
     * Users can view items from their own wishlists, admin/god can view all
     */
    public function index(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }

        return $user->can('ecommerce.wishlist-items.index')
            || $user->hasAnyRole(['god', 'admin', 'tech', 'customer']);
    }

    /**
     * Authenticated users can add items to their wishlists
     */
    public function store(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        return $user && ($user->can('ecommerce.wishlist-items.store')
            || $user->hasAnyRole(['god', 'admin', 'tech', 'customer']));
    }

    /**
     * Owner of wishlist, admin, or god can view item
     */
    public function show(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }

        // Tech users have read-only access to all wishlist items
        if ($user->hasAnyRole(['god', 'admin', 'tech'])) {
            return true;
        }

        // Check if user owns the wishlist
        $wishlist = $model->wishlist;
        if ($wishlist && isset($wishlist->user_id) && $wishlist->user_id === $user->id) {
            return true;
        }

        return Response::deny('You can only view items from your own wishlists.');
    }

    /**
     * Only owner of wishlist, admin, or god can update item
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

        // Check if user owns the wishlist
        $wishlist = $model->wishlist;
        if ($wishlist && isset($wishlist->user_id) && $wishlist->user_id === $user->id) {
            return true;
        }

        return Response::deny('You can only update items in your own wishlists.');
    }

    /**
     * Only owner of wishlist, admin, or god can delete item
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

        // Check if user owns the wishlist
        $wishlist = $model->wishlist;
        if ($wishlist && isset($wishlist->user_id) && $wishlist->user_id === $user->id) {
            return true;
        }

        return Response::deny('You can only remove items from your own wishlists.');
    }

    // =========================================================================
    // 5 RELATIONSHIP METHODS (Required by LaravelJsonApi\Contracts\Auth\Authorizer)
    // =========================================================================

    /**
     * Authorize showing related resources
     */
    public function showRelated(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->show($request, $model);
    }

    /**
     * Authorize showing a relationship
     */
    public function showRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->show($request, $model);
    }

    /**
     * Authorize updating a relationship
     */
    public function updateRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->update($request, $model);
    }

    /**
     * Authorize attaching to a relationship
     */
    public function attachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->update($request, $model);
    }

    /**
     * Authorize detaching from a relationship
     */
    public function detachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->update($request, $model);
    }
}
