<?php

namespace Modules\HR\JsonApi\V1\LeaveTypes;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer as AuthorizerContract;
use Modules\HR\Models\LeaveType;

class LeaveTypeAuthorizer implements AuthorizerContract
{
    /**
     * Authorize index (list) requests.
     */
    public function index(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        return $user?->can('hr.leave-types.index') ?? false;
    }

    /**
     * Authorize store (create) requests.
     */
    public function store(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        return $user?->can('hr.leave-types.store') ?? false;
    }

    /**
     * Authorize show (view) requests.
     */
    public function show(Request $request, $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('hr.leave-types.show') ?? false;
    }

    /**
     * Authorize update requests.
     */
    public function update(Request $request, $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('hr.leave-types.update') ?? false;
    }

    /**
     * Authorize destroy (delete) requests.
     */
    public function destroy(Request $request, $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('hr.leave-types.destroy') ?? false;
    }

    /**
     * Authorize showRelated requests.
     */
    public function showRelated(Request $request, $model, string $fieldName): bool|Response
    {
        $user = $request->user();
        return $user?->can('hr.leave-types.show-related') ?? false;
    }

    /**
     * Authorize showRelationship requests.
     */
    public function showRelationship(Request $request, $model, string $fieldName): bool|Response
    {
        $user = $request->user();
        return $user?->can('hr.leave-types.show-relationship') ?? false;
    }

    /**
     * Authorize updateRelationship requests.
     */
    public function updateRelationship(Request $request, $model, string $fieldName): bool|Response
    {
        $user = $request->user();
        return $user?->can('hr.leave-types.update-relationship') ?? false;
    }

    /**
     * Authorize attachRelationship requests.
     */
    public function attachRelationship(Request $request, $model, string $fieldName): bool|Response
    {
        $user = $request->user();
        return $user?->can('hr.leave-types.attach-relationship') ?? false;
    }

    /**
     * Authorize detachRelationship requests.
     */
    public function detachRelationship(Request $request, $model, string $fieldName): bool|Response
    {
        $user = $request->user();
        return $user?->can('hr.leave-types.detach-relationship') ?? false;
    }
}
