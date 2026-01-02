<?php

namespace Modules\Ecommerce\JsonApi\V1\ProductComparisonItems;

use Illuminate\Http\Request;
use Illuminate\Auth\Access\Response;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class ProductComparisonItemAuthorizer implements Authorizer
{
    public function index(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        return $user?->can('ecommerce.product-comparison-items.index') ?? false;
    }

    public function store(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }

        // If admin/god, allow
        if ($user->hasAnyRole(['god', 'admin'])) {
            return true;
        }

        // For customers, check if they own the comparison they're adding to
        $data = $request->json()->all();
        $comparisonId = null;

        // Check in attributes (comparisonId as attribute)
        if (isset($data['data']['attributes']['comparisonId'])) {
            $comparisonId = $data['data']['attributes']['comparisonId'];
        }
        // Also check in relationships (comparison as relationship)
        elseif (isset($data['data']['relationships']['comparison']['data']['id'])) {
            $comparisonId = $data['data']['relationships']['comparison']['data']['id'];
        }

        if ($comparisonId) {
            $comparison = \Modules\Ecommerce\Models\ProductComparison::find($comparisonId);

            if (!$comparison) {
                return false;
            }

            return $comparison->user_id === $user->id;
        }

        return false;
    }

    public function show(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }

        // Tech users have read-only access to all comparison items
        if ($user->hasAnyRole(['god', 'admin', 'tech'])) {
            return true;
        }

        // Can view if they can view the parent comparison
        $comparison = $model->comparison;
        if ($comparison->is_public) {
            return true;
        }

        return $comparison->user_id === $user->id;
    }

    public function update(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }

        $comparison = $model->comparison;

        if ($user->hasAnyRole(['god', 'admin'])) {
            return true;
        }

        return $comparison->user_id === $user->id;
    }

    public function destroy(Request $request, object $model): bool|Response
    {
        return $this->update($request, $model);
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
