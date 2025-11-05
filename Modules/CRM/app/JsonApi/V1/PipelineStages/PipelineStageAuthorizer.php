<?php

namespace Modules\CRM\JsonApi\V1\PipelineStages;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer as AuthorizerContract;
use Modules\CRM\Models\PipelineStage;

class PipelineStageAuthorizer implements AuthorizerContract
{
    /**
     * Authorize index (list) requests.
     */
    public function index(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        return $user?->can('crm.pipeline-stages.index') ?? false;
    }

    /**
     * Authorize store (create) requests.
     */
    public function store(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        return $user?->can('crm.pipeline-stages.store') ?? false;
    }

    /**
     * Authorize show (view) requests.
     */
    public function show(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('crm.pipeline-stages.show') ?? false;
    }

    /**
     * Authorize update requests.
     */
    public function update(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('crm.pipeline-stages.update') ?? false;
    }

    /**
     * Authorize destroy (delete) requests.
     */
    public function destroy(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        return $user?->can('crm.pipeline-stages.destroy') ?? false;
    }

    /**
     * Authorize showRelated requests.
     */
    public function showRelated(Request $request, object $model, string $fieldName): bool|Response
    {
        $user = $request->user();
        return $user?->can('crm.pipeline-stages.show-related') ?? false;
    }

    /**
     * Authorize showRelationship requests.
     */
    public function showRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        $user = $request->user();
        return $user?->can('crm.pipeline-stages.show-relationship') ?? false;
    }

    /**
     * Authorize updateRelationship requests.
     */
    public function updateRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        $user = $request->user();
        return $user?->can('crm.pipeline-stages.update-relationship') ?? false;
    }

    /**
     * Authorize attachRelationship requests.
     */
    public function attachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        $user = $request->user();
        return $user?->can('crm.pipeline-stages.attach-relationship') ?? false;
    }

    /**
     * Authorize detachRelationship requests.
     */
    public function detachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        $user = $request->user();
        return $user?->can('crm.pipeline-stages.detach-relationship') ?? false;
    }
}
