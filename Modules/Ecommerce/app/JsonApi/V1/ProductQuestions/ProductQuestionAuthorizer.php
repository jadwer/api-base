<?php

namespace Modules\Ecommerce\JsonApi\V1\ProductQuestions;

use Illuminate\Http\Request;
use Illuminate\Auth\Access\Response;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class ProductQuestionAuthorizer implements Authorizer
{
    public function index(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('ecommerce.product-questions.index') ?? false;
    }

    public function store(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('ecommerce.product-questions.store') ?? false;
    }

    public function show(Request $request, object $model): bool|Response
    {
        if ($model->status === 'approved') {
            return true;
        }
        $user = $request->user();
        if (!$user) {
            return false;
        }
        if ($user->can('ecommerce.product-questions.show')) {
            return true;
        }
        return $model->user_id === $user->id;
    }

    public function update(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }
        if ($user->can('ecommerce.product-questions.update')) {
            return true;
        }
        // Owner can only update their own pending questions
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
        if ($user->can('ecommerce.product-questions.destroy')) {
            return true;
        }
        // Owner can delete their own pending questions
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
