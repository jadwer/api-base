<?php

namespace Modules\CRM\JsonApi\V1\Opportunities;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer as AuthorizerContract;
use Modules\CRM\Models\Opportunity;

class OpportunityAuthorizer implements AuthorizerContract
{
    public function index(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('crm.opportunities.index') ?? false;
    }

    public function store(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('crm.opportunities.store') ?? false;
    }

    public function show(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('crm.opportunities.show') ?? false;
    }

    public function update(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('crm.opportunities.update') ?? false;
    }

    public function destroy(Request $request, object $model): bool|Response
    {
        return $request->user()?->can('crm.opportunities.destroy') ?? false;
    }

    public function showRelated(Request $request, object $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('crm.opportunities.show') ?? false;
    }

    public function showRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('crm.opportunities.show') ?? false;
    }

    public function updateRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('crm.opportunities.update') ?? false;
    }

    public function attachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('crm.opportunities.update') ?? false;
    }

    public function detachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $request->user()?->can('crm.opportunities.update') ?? false;
    }
}
