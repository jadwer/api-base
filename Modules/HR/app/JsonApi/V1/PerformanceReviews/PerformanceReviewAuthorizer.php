<?php

namespace Modules\HR\JsonApi\V1\PerformanceReviews;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer as AuthorizerContract;

class PerformanceReviewAuthorizer implements AuthorizerContract
{
    public function index(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('hr.performance-reviews.index') ?? false;
    }

    public function store(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('hr.performance-reviews.store') ?? false;
    }

    public function show(Request $request, $model): bool|Response
    {
        return $request->user()?->can('hr.performance-reviews.show') ?? false;
    }

    public function update(Request $request, $model): bool|Response
    {
        return $request->user()?->can('hr.performance-reviews.update') ?? false;
    }

    public function destroy(Request $request, $model): bool|Response
    {
        return $request->user()?->can('hr.performance-reviews.destroy') ?? false;
    }

    public function showRelated(Request $request, $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('hr.performance-reviews.show-related') ?? false;
    }

    public function showRelationship(Request $request, $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('hr.performance-reviews.show-relationship') ?? false;
    }

    public function updateRelationship(Request $request, $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('hr.performance-reviews.update-relationship') ?? false;
    }

    public function attachRelationship(Request $request, $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('hr.performance-reviews.attach-relationship') ?? false;
    }

    public function detachRelationship(Request $request, $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('hr.performance-reviews.detach-relationship') ?? false;
    }
}
