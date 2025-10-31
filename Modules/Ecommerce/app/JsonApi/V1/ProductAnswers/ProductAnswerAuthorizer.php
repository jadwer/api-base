<?php

namespace Modules\Ecommerce\JsonApi\V1\ProductAnswers;

use Illuminate\Http\Request;
use Illuminate\Auth\Access\Response;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class ProductAnswerAuthorizer implements Authorizer
{
    public function index(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        return $user?->can('ecommerce.product-answers.index') ?? false;
    }

    public function store(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }

        // Check permission
        if (!$user->can('ecommerce.product-answers.store')) {
            return false;
        }

        // Verify the question exists and is approved
        $data = $request->json()->all();
        if (isset($data['data']['relationships']['question']['data']['id'])) {
            $questionId = $data['data']['relationships']['question']['data']['id'];
            $question = \Modules\Ecommerce\Models\ProductQuestion::find($questionId);

            if (!$question) {
                return false;
            }

            // Only allow answers to approved questions
            if ($question->status !== 'approved') {
                return false;
            }
        }

        return true;
    }

    public function show(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }

        // Admin/tech can view all answers
        if ($user->hasAnyRole(['god', 'admin', 'tech'])) {
            return true;
        }

        // Users can view answers to approved questions
        $question = $model->question;
        if ($question && $question->status === 'approved') {
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

        // Admin/god can update any answer (including verification)
        if ($user->hasAnyRole(['god', 'admin'])) {
            return true;
        }

        // Users can update their own answers
        if ($model->user_id === $user->id) {
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

        // Admin/god can delete any answer
        if ($user->hasAnyRole(['god', 'admin'])) {
            return true;
        }

        // Users can delete their own answers
        if ($model->user_id === $user->id) {
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
