<?php

namespace Modules\Product\JsonApi\V1\PublicProducts;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class PublicProductAuthorizer implements Authorizer
{
    /**
     * Authorize the index controller action.
     * Allow public access for catalog browsing.
     */
    public function index(Request $request, string $modelClass): bool|Response
    {
        return true;
    }

    /**
     * Authorize the store controller action.
     * Not available for public catalog.
     */
    public function store(Request $request, string $modelClass): bool|Response
    {
        return false;
    }

    /**
     * Authorize the show controller action.
     * Allow public access for viewing product details.
     */
    public function show(Request $request, object $model): bool|Response
    {
        return true;
    }

    /**
     * Authorize the update controller action.
     * Not available for public catalog.
     */
    public function update(Request $request, object $model): bool|Response
    {
        return false;
    }

    /**
     * Authorize the destroy controller action.
     * Not available for public catalog.
     */
    public function destroy(Request $request, object $model): bool|Response
    {
        return false;
    }

    /**
     * Authorize the show-related controller action
     * Allow public access to related entities (unit, category, brand).
     */
    public function showRelated(Request $request, object $model, string $fieldName): bool|Response
    {
        return true;
    }

    /**
     * Authorize the show-relationship controller action.
     * Allow public access to relationship data.
     */
    public function showRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return true;
    }

    /**
     * Authorize the update-relationship controller action.
     * Not available for public catalog.
     */
    public function updateRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return false;
    }

    /**
     * Authorize the attach-relationship controller action.
     * Not available for public catalog.
     */
    public function attachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return false;
    }

    /**
     * Authorize the detach-relationship controller action.
     * Not available for public catalog.
     */
    public function detachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return false;
    }
}