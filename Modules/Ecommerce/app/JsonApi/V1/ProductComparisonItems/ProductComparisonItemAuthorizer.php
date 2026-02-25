<?php

namespace Modules\Ecommerce\JsonApi\V1\ProductComparisonItems;

use Illuminate\Http\Request;
use Illuminate\Auth\Access\Response;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class ProductComparisonItemAuthorizer implements Authorizer
{
    public function index(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('ecommerce.product-comparison-items.index') ?? false;
    }

    public function store(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }
        if ($user->can('ecommerce.product-comparison-items.store')) {
            return true;
        }
        // Ownership check: user must own the parent comparison
        $data = $request->json()->all();
        $comparisonId = null;
        if (isset($data['data']['attributes']['comparisonId'])) {
            $comparisonId = $data['data']['attributes']['comparisonId'];
        } elseif (isset($data['data']['relationships']['comparison']['data']['id'])) {
            $comparisonId = $data['data']['relationships']['comparison']['data']['id'];
        }
        if ($comparisonId) {
            $comparison = \Modules\Ecommerce\Models\ProductComparison::find($comparisonId);
            return $comparison && $comparison->user_id === $user->id;
        }
        return false;
    }

    public function show(Request $request, object $model): bool|Response
    {
        $comparison = $model->comparison;
        if ($comparison && $comparison->is_public) {
            return true;
        }
        $user = $request->user();
        if (!$user) {
            return false;
        }
        if ($user->can('ecommerce.product-comparison-items.show')) {
            return true;
        }
        return $comparison && $comparison->user_id === $user->id;
    }

    public function update(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }
        if ($user->can('ecommerce.product-comparison-items.update')) {
            return true;
        }
        $comparison = $model->comparison;
        return $comparison && $comparison->user_id === $user->id;
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
