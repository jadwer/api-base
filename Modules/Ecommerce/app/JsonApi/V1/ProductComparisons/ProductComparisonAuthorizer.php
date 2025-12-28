<?php

namespace Modules\Ecommerce\JsonApi\V1\ProductComparisons;

use Illuminate\Http\Request;
use Illuminate\Auth\Access\Response;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class ProductComparisonAuthorizer implements Authorizer
{
    public function index(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        return $user?->can('ecommerce.product-comparisons.index') ?? false;
    }

    public function store(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        return $user?->can('ecommerce.product-comparisons.store') ?? false;
    }

    public function show(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }

        // Tech users have read-only access to all comparisons
        if ($user->hasAnyRole(['god', 'admin', 'tech'])) {
            return true;
        }

        // Public comparisons are viewable by anyone authenticated
        if ($model->is_public) {
            return true;
        }

        // Private comparisons only viewable by owner
        return $model->user_id === $user->id;
    }

    public function update(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }

        if ($user->hasAnyRole(['god', 'admin'])) {
            return true;
        }

        return $model->user_id === $user->id;
    }

    public function destroy(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }

        if ($user->hasAnyRole(['god', 'admin'])) {
            return true;
        }

        return $model->user_id === $user->id;
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
