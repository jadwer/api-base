<?php

namespace Modules\HR\JsonApi\V1\Positions;

use Illuminate\Http\Request;
use Illuminate\Auth\Access\Response;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class PositionAuthorizer implements Authorizer
{
    // =========================================================================
    // 5 CRUD METHODS
    // =========================================================================

    public function index(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        return $user?->can('hr.positions.index') ?? false;
    }

    public function store(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        return $user?->can('hr.positions.store') ?? false;
    }

    public function show(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('hr.positions.show') ?? false;
    }

    public function update(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('hr.positions.update') ?? false;
    }

    public function destroy(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('hr.positions.destroy') ?? false;
    }

    // =========================================================================
    // 5 RELATIONSHIP METHODS (CRITICAL - DON'T FORGET THESE!)
    // =========================================================================

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
