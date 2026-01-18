<?php

namespace Modules\Sales\JsonApi\V1\QuoteItems;

use Illuminate\Http\Request;
use Illuminate\Auth\Access\Response;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class QuoteItemAuthorizer implements Authorizer
{
    /**
     * Authorize the index controller action.
     */
    public function index(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        return $user?->can('quote-items.index') ?? false;
    }

    /**
     * Authorize the store controller action.
     */
    public function store(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        return $user?->can('quote-items.store') ?? false;
    }

    /**
     * Authorize the show controller action.
     */
    public function show(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('quote-items.show') ?? false;
    }

    /**
     * Authorize the update controller action.
     */
    public function update(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('quote-items.update') ?? false;
    }

    /**
     * Authorize the destroy controller action.
     */
    public function destroy(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('quote-items.destroy') ?? false;
    }

    /**
     * Authorize the showRelated controller action.
     */
    public function showRelated(Request $request, object $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('quote-items.show') ?? false;
    }

    /**
     * Authorize the showRelationship controller action.
     */
    public function showRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('quote-items.show') ?? false;
    }

    /**
     * Authorize the updateRelationship controller action.
     */
    public function updateRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('quote-items.update') ?? false;
    }

    /**
     * Authorize the attachRelationship controller action.
     */
    public function attachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('quote-items.update') ?? false;
    }

    /**
     * Authorize the detachRelationship controller action.
     */
    public function detachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('quote-items.update') ?? false;
    }
}
