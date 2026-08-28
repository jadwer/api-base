<?php

namespace Modules\Billing\JsonApi\V1\DocumentLegends;

use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer as AuthorizerContract;

class DocumentLegendAuthorizer implements AuthorizerContract
{
    public function index(Request $request, string $modelClass): bool
    {
        return $request->user()?->can('billing.document-legends.index') ?? false;
    }

    public function show(Request $request, object $model): bool
    {
        return $request->user()?->can('billing.document-legends.show') ?? false;
    }

    public function store(Request $request, string $modelClass): bool
    {
        return $request->user()?->can('billing.document-legends.store') ?? false;
    }

    public function update(Request $request, object $model): bool
    {
        return $request->user()?->can('billing.document-legends.update') ?? false;
    }

    public function destroy(Request $request, object $model): bool
    {
        return $request->user()?->can('billing.document-legends.destroy') ?? false;
    }

    public function showRelated(Request $request, object $model, string $fieldName): bool
    {
        return $request->user()?->can('billing.document-legends.show') ?? false;
    }

    public function showRelationship(Request $request, object $model, string $fieldName): bool
    {
        return $request->user()?->can('billing.document-legends.show') ?? false;
    }

    public function updateRelationship(Request $request, object $model, string $fieldName): bool
    {
        return $request->user()?->can('billing.document-legends.update') ?? false;
    }

    public function attachRelationship(Request $request, object $model, string $fieldName): bool
    {
        return $request->user()?->can('billing.document-legends.update') ?? false;
    }

    public function detachRelationship(Request $request, object $model, string $fieldName): bool
    {
        return $request->user()?->can('billing.document-legends.update') ?? false;
    }
}
