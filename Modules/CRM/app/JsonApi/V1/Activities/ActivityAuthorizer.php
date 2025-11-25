<?php

namespace Modules\CRM\JsonApi\V1\Activities;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer as AuthorizerContract;
use Modules\CRM\Models\Activity;

class ActivityAuthorizer implements AuthorizerContract
{
    public function index(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('crm.activities.index') ?? false;
    }

    public function store(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('crm.activities.store') ?? false;
    }

    public function show(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('crm.activities.show') ?? false;
    }

    public function update(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('crm.activities.update') ?? false;
    }

    public function destroy(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('crm.activities.destroy') ?? false;
    }

    public function showRelated(Request $request, object $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('crm.activities.show') ?? false;
    }

    public function showRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('crm.activities.show') ?? false;
    }

    public function updateRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('crm.activities.update') ?? false;
    }

    public function attachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('crm.activities.update') ?? false;
    }

    public function detachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('crm.activities.update') ?? false;
    }
}
