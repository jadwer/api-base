<?php

namespace Modules\Inventory\JsonApi\V1\CycleCounts;

use LaravelJsonApi\Contracts\Auth\Authorizer;
use Illuminate\Http\Request;
use Modules\Inventory\Models\CycleCount;

class CycleCountAuthorizer implements Authorizer
{
    public function index(Request $request, string $modelClass): bool|\Illuminate\Auth\Access\Response
    {
        return $request->user()?->can('inventory.cycle-counts.index') ?? false;
    }

    public function show(?Request $request, CycleCount $model): bool|\Illuminate\Auth\Access\Response
    {
        return $request?->user()?->can('inventory.cycle-counts.show') ?? false;
    }

    public function store(Request $request, string $modelClass): bool|\Illuminate\Auth\Access\Response
    {
        return $request->user()?->can('inventory.cycle-counts.store') ?? false;
    }

    public function update(Request $request, CycleCount $model): bool|\Illuminate\Auth\Access\Response
    {
        return $request->user()?->can('inventory.cycle-counts.update') ?? false;
    }

    public function destroy(Request $request, CycleCount $model): bool|\Illuminate\Auth\Access\Response
    {
        return $request->user()?->can('inventory.cycle-counts.destroy') ?? false;
    }

    public function showRelated(Request $request, CycleCount $model, string $fieldName): bool|\Illuminate\Auth\Access\Response
    {
        return $request->user()?->can('inventory.cycle-counts.show') ?? false;
    }

    public function showRelationship(Request $request, CycleCount $model, string $fieldName): bool|\Illuminate\Auth\Access\Response
    {
        return $request->user()?->can('inventory.cycle-counts.show') ?? false;
    }

    public function updateRelationship(Request $request, CycleCount $model, string $fieldName): bool|\Illuminate\Auth\Access\Response
    {
        return $request->user()?->can('inventory.cycle-counts.update') ?? false;
    }

    public function attachRelationship(Request $request, CycleCount $model, string $fieldName): bool|\Illuminate\Auth\Access\Response
    {
        return $request->user()?->can('inventory.cycle-counts.update') ?? false;
    }

    public function detachRelationship(Request $request, CycleCount $model, string $fieldName): bool|\Illuminate\Auth\Access\Response
    {
        return $request->user()?->can('inventory.cycle-counts.update') ?? false;
    }
}
