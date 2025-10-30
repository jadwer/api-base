<?php

namespace Modules\Ecommerce\JsonApi\V1\ProductReviews;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class ProductReviewAuthorizer implements Authorizer
{
    /**
     * Anyone can view approved reviews (including guests)
     */
    public function index(Request $request, string $modelClass): bool|Response
    {
        // Public endpoint - guests can view approved reviews
        return true;
    }

    /**
     * Authenticated users can create reviews
     */
    public function store(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        return $user && ($user->can('ecommerce.product-reviews.store')
            || $user->hasAnyRole(['god', 'admin', 'tech', 'customer']));
    }

    /**
     * Anyone can view a single approved review (including guests)
     */
    public function show(Request $request, object $model): bool|Response
    {
        // If it's approved, anyone can see it
        if ($model->status === 'approved') {
            return true;
        }

        // If not approved, only owner, admin, or god can see it
        $user = $request->user();
        if (!$user) {
            return Response::deny('Only approved reviews are public.');
        }

        if ($user->hasAnyRole(['god', 'admin'])) {
            return true;
        }

        if (isset($model->user_id) && $model->user_id === $user->id) {
            return true;
        }

        return Response::deny('You can only view approved reviews or your own reviews.');
    }

    /**
     * Only owner or admin can update review
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

        return Response::deny('You can only update your own reviews.');
    }

    /**
     * Only owner, admin, or tech can delete review
     */
    public function destroy(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }

        if ($user->hasAnyRole(['god', 'admin', 'tech'])) {
            return true;
        }

        if (isset($model->user_id) && $model->user_id === $user->id) {
            return true;
        }

        return Response::deny('You can only delete your own reviews.');
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
        return false;
    }

    public function attachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return false;
    }

    public function detachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return false;
    }
}
