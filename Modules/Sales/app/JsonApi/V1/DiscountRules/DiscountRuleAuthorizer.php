<?php

namespace Modules\Sales\JsonApi\V1\DiscountRules;

use LaravelJsonApi\Contracts\Auth\Authorizer;
use Illuminate\Http\Request;
use Modules\Sales\Models\DiscountRule;

class DiscountRuleAuthorizer implements Authorizer
{
    public function index(Request $request, string $modelClass): bool|\Illuminate\Auth\Access\Response
    {
        return $request->user()?->can('discount-rules.index') ?? false;
    }

    public function show(?Request $request, DiscountRule $model): bool|\Illuminate\Auth\Access\Response
    {
        return $request?->user()?->can('discount-rules.show') ?? false;
    }

    public function store(Request $request, string $modelClass): bool|\Illuminate\Auth\Access\Response
    {
        return $request->user()?->can('discount-rules.store') ?? false;
    }

    public function update(Request $request, DiscountRule $model): bool|\Illuminate\Auth\Access\Response
    {
        return $request->user()?->can('discount-rules.update') ?? false;
    }

    public function destroy(Request $request, DiscountRule $model): bool|\Illuminate\Auth\Access\Response
    {
        return $request->user()?->can('discount-rules.destroy') ?? false;
    }

    public function showRelated(Request $request, DiscountRule $model, string $fieldName): bool|\Illuminate\Auth\Access\Response
    {
        return $request->user()?->can('discount-rules.show') ?? false;
    }

    public function showRelationship(Request $request, DiscountRule $model, string $fieldName): bool|\Illuminate\Auth\Access\Response
    {
        return $request->user()?->can('discount-rules.show') ?? false;
    }

    public function updateRelationship(Request $request, DiscountRule $model, string $fieldName): bool|\Illuminate\Auth\Access\Response
    {
        return $request->user()?->can('discount-rules.update') ?? false;
    }

    public function attachRelationship(Request $request, DiscountRule $model, string $fieldName): bool|\Illuminate\Auth\Access\Response
    {
        return $request->user()?->can('discount-rules.update') ?? false;
    }

    public function detachRelationship(Request $request, DiscountRule $model, string $fieldName): bool|\Illuminate\Auth\Access\Response
    {
        return $request->user()?->can('discount-rules.update') ?? false;
    }
}
