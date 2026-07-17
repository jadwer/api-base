<?php

namespace Modules\Product\JsonApi\V1\PublicCategories;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;

/**
 * Read-only public authorizer for the categories navigation of the public
 * catalog. Mirrors PublicProductAuthorizer: index/show open to guests, every
 * write action denied.
 */
class PublicCategoryAuthorizer implements Authorizer
{
    public function index(Request $request, string $modelClass): bool|Response
    {
        return true;
    }

    public function store(Request $request, string $modelClass): bool|Response
    {
        return false;
    }

    public function show(Request $request, object $model): bool|Response
    {
        return true;
    }

    public function update(Request $request, object $model): bool|Response
    {
        return false;
    }

    public function destroy(Request $request, object $model): bool|Response
    {
        return false;
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
