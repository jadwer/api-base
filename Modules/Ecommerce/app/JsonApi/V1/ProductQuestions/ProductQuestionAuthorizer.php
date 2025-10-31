<?php

namespace Modules\Ecommerce\JsonApi\V1\ProductQuestions;

use Illuminate\Http\Request;
use Illuminate\Auth\Access\Response;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class ProductQuestionAuthorizer implements Authorizer
{
    public function index(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        return $user?->can('ecommerce.product-questions.index') ?? false;
    }

    public function store(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        return $user?->can('ecommerce.product-questions.store') ?? false;
    }

    public function show(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }

        // Admin/tech can view all
        if ($user->hasAnyRole(['god', 'admin', 'tech'])) {
            return true;
        }

        // Users can view approved questions or their own questions
        if ($model->status === 'approved' || $model->user_id === $user->id) {
            return true;
        }

        return false;
    }

    public function update(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }

        // Admin/god can update any question (including status changes)
        if ($user->hasAnyRole(['god', 'admin'])) {
            return true;
        }

        // Tech can update status for moderation
        if ($user->hasRole('tech')) {
            return $user->can('ecommerce.product-questions.update');
        }

        // Users can only update their own pending questions (not status)
        if ($model->user_id === $user->id && $model->status === 'pending') {
            return true;
        }

        return false;
    }

    public function destroy(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }

        // Admin/god can delete any question
        if ($user->hasAnyRole(['god', 'admin'])) {
            return true;
        }

        // Users can delete their own pending questions
        if ($model->user_id === $user->id && $model->status === 'pending') {
            return true;
        }

        return false;
    }

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
