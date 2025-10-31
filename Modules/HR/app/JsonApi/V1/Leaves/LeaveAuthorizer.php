<?php

namespace Modules\HR\JsonApi\V1\Leaves;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer as AuthorizerContract;
use Modules\HR\Models\Leave;

class LeaveAuthorizer implements AuthorizerContract
{
    /**
     * Authorize index (list) requests.
     */
    public function index(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        return $user?->can('hr.leaves.index') ?? false;
    }

    /**
     * Authorize store (create) requests.
     */
    public function store(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        return $user?->can('hr.leaves.store') ?? false;
    }

    /**
     * Authorize show (view) requests.
     */
    public function show(Request $request, $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('hr.leaves.show') ?? false;
    }

    /**
     * Authorize update requests.
     */
    public function update(Request $request, $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('hr.leaves.update') ?? false;
    }

    /**
     * Authorize destroy (delete) requests.
     */
    public function destroy(Request $request, $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('hr.leaves.destroy') ?? false;
    }

    /**
     * Authorize showRelated requests.
     */
    public function showRelated(Request $request, $model, string $fieldName): bool|Response
    {
        $user = $request->user();
        return $user?->can('hr.leaves.show-related') ?? false;
    }

    /**
     * Authorize showRelationship requests.
     */
    public function showRelationship(Request $request, $model, string $fieldName): bool|Response
    {
        $user = $request->user();
        return $user?->can('hr.leaves.show-relationship') ?? false;
    }

    /**
     * Authorize updateRelationship requests.
     */
    public function updateRelationship(Request $request, $model, string $fieldName): bool|Response
    {
        $user = $request->user();
        return $user?->can('hr.leaves.update-relationship') ?? false;
    }

    /**
     * Authorize attachRelationship requests.
     */
    public function attachRelationship(Request $request, $model, string $fieldName): bool|Response
    {
        $user = $request->user();
        return $user?->can('hr.leaves.attach-relationship') ?? false;
    }

    /**
     * Authorize detachRelationship requests.
     */
    public function detachRelationship(Request $request, $model, string $fieldName): bool|Response
    {
        $user = $request->user();
        return $user?->can('hr.leaves.detach-relationship') ?? false;
    }
}
